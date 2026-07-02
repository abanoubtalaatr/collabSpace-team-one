<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
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
            'start_date' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(ProjectPriority::values())],
            'status' => ['required', Rule::in(ProjectStatus::values())],
            'type' => ['nullable', 'string', 'max:255'],
            // 'team_ids' => ['sometimes', 'array'],
            // 'team_ids.*' => ['integer', 'exists:teams,id'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
