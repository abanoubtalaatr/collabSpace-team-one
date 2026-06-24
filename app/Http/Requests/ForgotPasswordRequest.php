<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', Rule::exists('users', 'email')]
        ];
    }
}
