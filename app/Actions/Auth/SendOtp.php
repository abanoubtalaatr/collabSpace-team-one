<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Mail\sendOtp as SendOtpMail;
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
        // FIXME: fix in production
        // $otp = (string) random_int(100000, 999999);
        $otp = '123456';

        defer(function () use ($email, $otp, $recipientName) {
            Mail::to($email)->send(new SendOtpMail($otp, $recipientName ?? 'there'));
        });

        Cache::put("{$purpose}_otp_{$email}", [
            'otp' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5)
        ], now()->addMinutes(5));
    }
}
