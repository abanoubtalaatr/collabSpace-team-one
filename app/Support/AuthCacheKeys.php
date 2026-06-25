<?php

declare(strict_types=1);

namespace App\Support;

final class AuthCacheKeys
{
    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function otp(string $purpose, string $email): string
    {
        return "{$purpose}_otp_".self::normalizeEmail($email);
    }

    public static function passwordResetToken(string $email): string
    {
        return 'password_reset_token_'.self::normalizeEmail($email);
    }
}
