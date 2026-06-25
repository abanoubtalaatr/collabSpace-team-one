<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\AuthCacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyOtp
{
    private const RESET_TOKEN_TTL_MINUTES = 15;

    /**
     * @param  'registration'|'password_reset'|'forgot_password'  $purpose
     * @return array{user?: User, token?: string, reset_token?: string}
     */
    public function handle(string $email, string $otp, string $purpose): array
    {
        $email = AuthCacheKeys::normalizeEmail($email);
        $purpose = $this->normalizePurpose($purpose);

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
            Cache::put($key, $cached, now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES));

            throw ValidationException::withMessages(['otp' => 'Invalid OTP.']);
        }

        Cache::forget($key);

        if ($purpose === 'registration') {
            return $this->handleRegistrationVerification($email);
        }

        return $this->handlePasswordResetVerification($email);
    }

    private function normalizePurpose(string $purpose): string
    {
        return match (strtolower(trim($purpose))) {
            'forgot_password', 'password_reset' => 'password_reset',
            'registration' => 'registration',
            default => $purpose,
        };
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

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]
        );

        return ['reset_token' => $resetToken];
    }
}
