<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\AuthCacheKeys;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    private const RESET_TOKEN_TTL_MINUTES = 15;

    /**
     * @return array{user: User, token: string}
     */
    public function handle(array $data): array
    {
        $email = AuthCacheKeys::normalizeEmail($data['email']);
        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'reset_token' => 'Reset session expired. Verify OTP first with purpose password_reset.',
            ]);
        }

        $createdAt = Carbon::parse($record->created_at);

        if ($createdAt->lte(now()->subMinutes(self::RESET_TOKEN_TTL_MINUTES))) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages(['reset_token' => 'Reset session expired.']);
        }

        if (! Hash::check($data['reset_token'], $record->token)) {
            throw ValidationException::withMessages(['reset_token' => 'Invalid reset token.']);
        }

        $user = User::query()->where('email', $email)->firstOrFail();

        return DB::transaction(function () use ($email, $data, $user): array {
            $user->update(['password' => $data['password']]);

            $accessToken = $user->createToken($user->email);

            DB::afterCommit(function () use ($email): void {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            });

            return [
                'user' => $user,
                'token' => $accessToken->plainTextToken,
            ];
        });
    }
}
