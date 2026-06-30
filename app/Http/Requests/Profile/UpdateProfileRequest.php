<?php

namespace App\Http\Requests\Profile;

use App\Enums\UserAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'about' => ['nullable', 'string', 'max:2000'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'availability_status' => ['sometimes', Rule::in(UserAvailability::values())],
            'current_team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'current_project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $data = $this->safe()->only([
            'name',
            'email',
            'phone',
            'country_code',
            'about',
            'job_title',
            'availability_status',
            'current_team_id',
            'current_project_id',
        ]);

        if ($this->has('experience_years')) {
            $data['exp'] = $this->integer('experience_years');
        }

        return $data;
    }
}
