<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    /**
     * @return array{user: User, token: string}
     */
    public function handle(array $data): array
    {
        $cacheKey = "password_reset_token_{$data['email']}";
        $cachedTokenHash = Cache::get($cacheKey);

        if (! $cachedTokenHash) {
            throw ValidationException::withMessages(['reset_token' => 'Reset session expired.']);
        }

        if (! Hash::check($data['reset_token'], $cachedTokenHash)) {
            throw ValidationException::withMessages(['reset_token' => 'Invalid reset token.']);
        }

        $user = User::query()->where('email', $data['email'])->firstOrFail();

        return DB::transaction(function () use ($cacheKey, $data, $user): array {
            $user->update(['password' => $data['password']]);

            $accessToken = $user->createToken($user->email);

            DB::afterCommit(fn () => Cache::forget($cacheKey));

            return [
                'user' => $user,
                'token' => $accessToken->plainTextToken,
            ];
        });
    }
}
