<?php

namespace App\Http\Requests\Team;

use App\Http\Requests\Concerns\ParsesMethodBody;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
{
    use ParsesMethodBody;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeBodyParameters(['name', 'display_name', 'description']);

        // Keep display_name in sync when only name is sent (same behavior as store).
        if ($this->filled('name') && ! $this->exists('display_name')) {
            $this->merge([
                'display_name' => $this->input('name'),
            ]);
        }
    }

    public function rules(): array
    {
        $team = $this->route('team');
        $teamId = is_object($team) ? $team->getKey() : $team;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('teams', 'name')->ignore($teamId)],
            'display_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
