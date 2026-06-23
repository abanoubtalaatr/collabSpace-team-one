<?php

namespace App\Services;

use App\Contracts\GloballySearchable;
use App\Search\SearchRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Searchable\Search;
use Spatie\Searchable\SearchResult;

class GlobalSearchService
{
    public function __construct(
        private readonly Search $search,
        private readonly SearchRegistry $registry,
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

        foreach ($this->registry->resolveByType($type) as $modelClass) {
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
     * @param  class-string<Model&GloballySearchable>  $modelClass
     * @return array<int, string>
     */
    private function resolveFields(string $modelClass, ?string $field): array
    {
        $allowedFields = $modelClass::searchFields();

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
        /** @var Model&GloballySearchable $searchable */
        $searchable = $result->searchable;

        return [
            'type' => $result->type,
            'id' => $searchable->getKey(),
            'title' => $searchable->searchTitle(),
            'source' => [
                'table' => $searchable->getTable(),
                'column' => $searchable->matchedSearchColumn($query),
            ],
            'data' => $searchable->toSearchPayload(),
        ];
    }
}
