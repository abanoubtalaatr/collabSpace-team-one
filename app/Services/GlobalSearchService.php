<?php

namespace App\Services;

use App\Contracts\GlobalSearchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Searchable\Search;
use Spatie\Searchable\SearchResult;
use UnexpectedValueException;

class GlobalSearchService
{
    public function __construct(
        private readonly Search $search,
        private readonly GlobalSearchModelRegistry $registry,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, ?string $type = null, ?string $field = null): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        $models = $this->registry->resolveModels($type);

        foreach ($models as $modelClass) {
            $searchFields = $this->resolveFields($modelClass, $field);

            if ($searchFields === []) {
                continue;
            }

            $this->search->registerModel($modelClass, $searchFields);
        }

        $results = $this->search
            ->limitAspectResults(20)
            ->search($query);

        return $results->map(fn (SearchResult $result) => $this->formatResult($result, $query));
    }

    /**
     * @param  class-string<Model&GlobalSearchable>  $modelClass
     * @return array<int, string>
     */
    private function resolveFields(string $modelClass, ?string $field): array
    {
        $allowedFields = $modelClass::globalSearchColumns();

        if ($field === null || $field === '') {
            return $allowedFields;
        }

        return in_array($field, $allowedFields, true) ? [$field] : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatResult(SearchResult $result, string $query): array
    {
        $searchable = $result->searchable;

        if (! $searchable instanceof Model || ! $searchable instanceof GlobalSearchable) {
            throw new UnexpectedValueException('Global search results must reference searchable Eloquent models.');
        }

        return [
            'type' => $result->type,
            'id' => $searchable->getKey(),
            'title' => $result->title,
            'source' => $this->determineMatchedSource($searchable, $query),
            'data' => $this->searchableData($searchable),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function determineMatchedSource(Model $searchable, string $query): array
    {
        $table = $searchable->getTable();
        $matchedColumn = $this->findMatchedColumn($searchable, $query);
        $matchedValue = $matchedColumn === ''
            ? ''
            : (string) data_get($searchable, $matchedColumn, '');

        return [
            'table' => $table,
            'column' => $matchedColumn,
            'value' => $matchedValue,
        ];
    }

    private function findMatchedColumn(Model $searchable, string $query): string
    {
        $allowedFields = $searchable instanceof GlobalSearchable
            ? $searchable::globalSearchColumns()
            : [];
        $terms = preg_split('/\s+/', $query) ?: [];
        $terms = array_values(array_filter(
            array_map(fn (string $term): string => mb_strtolower(trim($term), 'UTF-8'), $terms),
            fn (string $term): bool => $term !== ''
        ));

        foreach ($allowedFields as $field) {
            $value = mb_strtolower((string) data_get($searchable, $field), 'UTF-8');

            foreach ($terms as $term) {
                if (str_contains($value, $term)) {
                    return $field;
                }
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function searchableData(Model $searchable): array
    {
        if ($searchable instanceof GlobalSearchable) {
            $searchable->loadMissing($searchable::globalSearchRelations());
        }

        return $searchable->toArray();
    }
}
