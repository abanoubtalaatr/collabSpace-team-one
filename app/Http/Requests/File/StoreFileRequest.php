<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File as FileRule;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                FileRule::types([
                    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt',
                    'jpg', 'jpeg', 'png', 'gif', 'webp',
                ])->max('20mb'),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id', 'prohibited_if:task_id,*'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id', 'prohibited_if:project_id,*'],
        ];
    }
}
