<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\AuthCacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyOtp
{
    /**
     * @param  'registration'|'password_reset'  $purpose
     * @return array{user?: User, token?: string, reset_token?: string}
     */
    public function handle(string $email, string $otp, string $purpose): array
    {
        $email = AuthCacheKeys::normalizeEmail($email);
        $key = AuthCacheKeys::otp($purpose, $email);
        $cached = Cache::get($key);

        if (! $cached) {
            throw ValidationException::withMessages(['otp' => 'OTP expired or invalid.']);
        }

        if ($cached['attempts'] >= 3) {
            Cache::forget($key);

            throw ValidationException::withMessages(['otp' => 'Too many attempts.']);
        }

        if (! Hash::check($otp, $cached['otp'])) {
            $cached['attempts']++;
            Cache::put($key, $cached, now()->addMinutes(15));

            throw ValidationException::withMessages(['otp' => 'Invalid OTP.']);
        }

        Cache::forget($key);

        if ($purpose === 'registration') {
            return $this->handleRegistrationVerification($email);
        }

        return $this->handlePasswordResetVerification($email);
    }

    /**
     * @return array{user: User, token: string}
     */
    private function handleRegistrationVerification(string $email): array
    {
        $user = User::query()->where('email', $email)->firstOrFail();

        $user->forceFill([
            'email_verified_at' => now(),
        ])->save();

        $accessToken = $user->createToken($user->email);

        return [
            'user' => $user,
            'token' => $accessToken->plainTextToken,
        ];
    }

    /**
     * @return array{reset_token: string}
     */
    private function handlePasswordResetVerification(string $email): array
    {
        $resetToken = Str::random(40);

        Cache::put(
            AuthCacheKeys::passwordResetToken($email),
            Hash::make($resetToken),
            now()->addMinutes(15),
        );

        return ['reset_token' => $resetToken];
    }
}
