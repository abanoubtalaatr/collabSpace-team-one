<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUser
{
    /**
     * @return array{user: User, token: string}
     */
    public function handle(array $data): array
    {
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages(['email' => __('auth.unverified')]);
        }

        $accessToken = $user->createToken($user->email);

        return [
            'user' => $user,
            'token' => $accessToken->plainTextToken
        ];
    }
}
