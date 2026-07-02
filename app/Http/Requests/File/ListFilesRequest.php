<?php

namespace App\Http\Requests\File;

use App\Enums\FileStatus;
use App\Enums\FileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListFilesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(FileStatus::values())],
            'file_type' => ['sometimes', Rule::in(FileType::values())],
            'extension' => ['sometimes', 'string', 'max:20'],
            'project_id' => ['sometimes', 'integer', 'exists:projects,id'],
            'task_id' => ['sometimes', 'integer', 'exists:tasks,id'],
            'mine' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
