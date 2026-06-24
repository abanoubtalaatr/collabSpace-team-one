<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
