<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'purpose' => ['required', Rule::in(['registration', 'password_reset'])],
        ];
    }
}
