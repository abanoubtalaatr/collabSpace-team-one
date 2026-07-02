<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attachable_type' => ['required', Rule::in(['project', 'task'])],
            'attachable_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
