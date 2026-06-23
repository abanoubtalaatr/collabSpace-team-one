<?php

namespace App\Concerns;

use App\Contracts\GloballySearchable;
use App\Search\SearchRegistry;

trait InteractsWithGlobalSearch
{
    protected static function bootInteractsWithGlobalSearch(): void
    {
        static::booted(function (): void {
            if (is_subclass_of(static::class, GloballySearchable::class)) {
                app(SearchRegistry::class)->register(static::class);
            }
        });
    }

    public function matchedSearchColumn(string $query): string
    {
        $terms = array_values(array_filter(array_map('trim', preg_split('/\s+/', $query))));

        foreach (static::searchFields() as $field) {
            $value = mb_strtolower((string) data_get($this, $field), 'UTF8');

            foreach ($terms as $term) {
                $term = mb_strtolower($term, 'UTF8');

                if ($term !== '' && str_contains($value, $term)) {
                    return $field;
                }
            }
        }

        return '';
    }
}
