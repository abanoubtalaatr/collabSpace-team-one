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
            'name' => ['required', 'string', 'min:10', 'max:255'],
            'email' => ['required', 'string', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
