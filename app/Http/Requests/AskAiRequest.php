<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
