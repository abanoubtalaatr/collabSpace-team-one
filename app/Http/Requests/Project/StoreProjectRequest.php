<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', Rule::date()->todayOrAfter()],
            'deadline' => ['nullable', Rule::date()->todayOrAfter(), 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(ProjectPriority::values())],
            'status' => ['sometimes', Rule::in(ProjectStatus::values())],
            'type' => ['nullable', 'string', 'max:255'],
            // 'team_ids' => ['sometimes', 'array'],
            // 'team_ids.*' => ['integer', 'exists:teams,id'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240'],
            // guests to project
            'guest_ids' => ['sometimes', 'array'],
            'guest_ids.*' => ['integer', Rule::exists('users', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'The project start date cannot be in the past.',
            'deadline.after_or_equal' => 'The project deadline cannot be in the past and must be on or after the start date.',
        ];
    }
}
