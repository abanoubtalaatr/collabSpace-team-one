<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use NormalizesEmail;

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^(?=.*\p{L})[\p{L}\p{M}\s\'\-\.]+$/u',
            ],
            'email' => ['required', 'string', 'email:rfc,filter', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'The name must contain letters and cannot be only numbers or special characters.',
            'email.email' => 'The email format is invalid.',
        ];
    }
}
