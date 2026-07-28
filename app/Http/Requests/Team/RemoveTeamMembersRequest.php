<?php

namespace App\Http\Requests\Team;

use App\Http\Requests\Concerns\ParsesMethodBody;
use Illuminate\Foundation\Http\FormRequest;

class RemoveTeamMembersRequest extends FormRequest
{
    use ParsesMethodBody;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeBodyParameters(['user_ids']);

        $userIds = $this->input('user_ids');

        if (is_string($userIds)) {
            $decoded = json_decode($userIds, true);
            $userIds = json_last_error() === JSON_ERROR_NONE
                ? $decoded
                : array_filter(array_map('trim', explode(',', $userIds)));
        }

        if (is_array($userIds)) {
            $this->merge([
                'user_ids' => array_values(array_map('intval', $userIds)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
