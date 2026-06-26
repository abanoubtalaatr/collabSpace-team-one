<?php

namespace App\Enums;

enum FileStatus: string
{
    case Attached = 'attached';
    case Detached = 'detached';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
