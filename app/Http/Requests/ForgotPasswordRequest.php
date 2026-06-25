<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    use NormalizesEmail;

    public function rules(): array
    {
        return [
            'email' => ['required', Rule::exists('users', 'email')],
        ];
    }
}
