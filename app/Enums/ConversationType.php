<?php

namespace App\Enums;

enum ConversationType: string
{
    case Project = 'project';
    case Direct = 'direct';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
