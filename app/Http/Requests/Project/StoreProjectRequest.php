<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via middleware/policy
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'start_date'    => ['nullable', 'date'],
            'deadline'      => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority'      => ['required', Rule::in(Project::priorities())],
            'status'        => ['sometimes', Rule::in(Project::statuses())],
            'team_ids'      => ['sometimes', 'array'],
            'team_ids.*'    => ['integer', 'exists:teams,id'],
            'attachments'   => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240'], // 10MB per file
        ];
    }
}