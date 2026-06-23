<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Searchable\Search;
use Spatie\Searchable\SearchResult;

class GlobalSearchService
{
    private const SEARCH_FIELDS = [
        User::class => ['name', 'email'],
        Project::class => ['name', 'description'],
        Task::class => ['name', 'description'],
        Team::class => ['name'],
        Role::class => ['name'],
    ];

    private const TYPE_MODEL_MAP = [
        'user' => User::class,
        'project' => Project::class,
        'task' => Task::class,
        'team' => Team::class,
        'role' => Role::class,
    ];

    public function __construct(
        private readonly Search $search,
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

        $models = $this->resolveModels($type);

        foreach ($models as $modelClass) {
            $searchFields = $this->resolveFields($modelClass, $field);

            if (empty($searchFields)) {
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
     * @return array<int, class-string<Model>>
     */
    private function resolveModels(?string $type): array
    {
        if ($type === null || $type === '') {
            return array_keys(self::SEARCH_FIELDS);
        }

        $type = strtolower($type);

        return array_key_exists($type, self::TYPE_MODEL_MAP)
            ? [self::TYPE_MODEL_MAP[$type]]
            : array_keys(self::SEARCH_FIELDS);
    }

    /**
     * @param class-string<Model> $modelClass
     * @return array<int, string>
     */
    private function resolveFields(string $modelClass, ?string $field): array
    {
        $allowedFields = self::SEARCH_FIELDS[$modelClass] ?? [];

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

        return [
            'table' => $table,
            'column' => $matchedColumn,
        ];
    }

    private function findMatchedColumn(Model $searchable, string $query): string
    {
        $modelClass = get_class($searchable);
        $allowedFields = self::SEARCH_FIELDS[$modelClass] ?? [];
        $terms = array_values(array_filter(array_map('trim', preg_split('/\s+/', $query))));

        foreach ($allowedFields as $field) {
            $value = mb_strtolower((string) data_get($searchable, $field), 'UTF8');

            foreach ($terms as $term) {
                $term = mb_strtolower($term, 'UTF8');

                if ($term !== '' && str_contains($value, $term)) {
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
        match (true) {
            $searchable instanceof User => $searchable->loadMissing([
                'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
                'teams:id,name,display_name,description,created_at,updated_at',
                'tasks:id,project_id,name,description,created_at,updated_at',
                'roles:id,name,display_name,description,created_at,updated_at',
            ]),
            $searchable instanceof Project => $searchable->loadMissing([
                'creator:id,name,email,email_verified_at,created_at,updated_at',
                'teams:id,name,display_name,description,created_at,updated_at',
                'tasks:id,project_id,name,description,created_at,updated_at',
            ]),
            $searchable instanceof Task => $searchable->loadMissing([
                'project:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
                'users:id,name,email,email_verified_at,created_at,updated_at',
            ]),
            $searchable instanceof Team => $searchable->loadMissing([
                'members:id,name,email,email_verified_at,created_at,updated_at',
                'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            ]),
            $searchable instanceof Role => $searchable->loadMissing([
                'users:id,name,email,email_verified_at,created_at,updated_at',
            ]),
            default => null,
        };

        return $searchable->toArray();
    }
}
