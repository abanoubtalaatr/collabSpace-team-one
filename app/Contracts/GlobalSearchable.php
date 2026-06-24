<?php

declare(strict_types=1);

namespace App\Contracts;

interface GlobalSearchable
{
    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array;

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array;

    public static function globalSearchType(): string;
}
