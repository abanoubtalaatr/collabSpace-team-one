<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Mail\sendOtp as SendOtpMail;
use App\Models\User;
use App\Support\AuthCacheKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SendOtp
{
    /**
     * @param  'registration'|'password_reset'  $purpose
     */
    public function handle(string $email, string $purpose, ?string $recipientName = null): void
    {
        $email = AuthCacheKeys::normalizeEmail($email);

        $recipientName = $recipientName
            ?? User::query()->where('email', $email)->value('name')
            ?? 'there';

        if (app()->isProduction()) {
            $otp = (string) random_int(100000, 999999);
        } else {
            $otp = '123456';
        }

        $ttl = now()->addMinutes(15);

        Cache::put(AuthCacheKeys::otp($purpose, $email), [
            'otp' => Hash::make($otp),
            'attempts' => 0,
        ], $ttl);

        Mail::to($email)->send(new SendOtpMail($otp, $recipientName));
    }
}
