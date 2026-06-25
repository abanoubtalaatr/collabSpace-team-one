<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    use NormalizesEmail;

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
