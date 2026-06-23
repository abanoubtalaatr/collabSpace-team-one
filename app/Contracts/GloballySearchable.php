<?php

namespace App\Contracts;

interface GloballySearchable
{
    public static function searchKey(): string;

    /**
     * @return array<int, string>
     */
    public static function searchFields(): array;

    public function searchTitle(): string;

    public function matchedSearchColumn(string $query): string;

    /**
     * @return array<string, mixed>
     */
    public function toSearchPayload(): array;
}
