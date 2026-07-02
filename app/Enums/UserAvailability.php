<?php

namespace App\Enums;

enum UserAvailability: string
{
    case Available = 'available';
    case Unavailable = 'unavailable';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
