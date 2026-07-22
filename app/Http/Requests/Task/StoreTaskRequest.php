<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('title')) {
            $this->merge(['title' => $this->input('name')]);
        }

        $this->merge([
            'progress' => $this->input('progress', 0),
            'status' => $this->input('status', TaskStatus::Pending->value),
            'start_date' => $this->input('start_date', now()->toDateString()),
            'due_date' => $this->input('due_date', now()->addWeek()->toDateString()),
        ]);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', Rule::date()->todayOrAfter()],
            'due_date' => ['required', 'date', 'after_or_equal:start_date'],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'status' => ['sometimes', Rule::in(TaskStatus::values())],
            'priority' => ['required', Rule::in(TaskPriority::values())],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'The task start date cannot be in the past.',
            'due_date.after_or_equal' => 'The task due date must be on or after the start date.',
        ];
    }
}
