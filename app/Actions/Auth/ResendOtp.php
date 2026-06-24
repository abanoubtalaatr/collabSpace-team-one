<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class ResendOtp
{
    public function __construct(private SendOtp $sendOtp) {}

    public function handle(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => __('auth.not_found')]);
        }

        if ($user->email_verified_at) {
            throw ValidationException::withMessages(['email' => __('auth.already_verified')]);
        }

        $this->sendOtp->handle($email, 'registration');
    }
}
