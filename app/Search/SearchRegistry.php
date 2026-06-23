<?php

namespace App\Search;

use App\Contracts\GloballySearchable;
use Illuminate\Database\Eloquent\Model;

class SearchRegistry
{
    /** @var array<int, class-string<Model&GloballySearchable>> */
    private array $models = [];

    private bool $initialized = false;

    /**
     * @param  class-string<Model&GloballySearchable>  $modelClass
     */
    public function register(string $modelClass): void
    {
        if (! in_array($modelClass, $this->models, true)) {
            $this->models[] = $modelClass;
        }
    }

    /**
     * @return array<int, class-string<Model&GloballySearchable>>
     */
    public function all(): array
    {
        $this->initialize();

        return $this->models;
    }

    /**
     * @return array<int, class-string<Model&GloballySearchable>>
     */
    public function resolveByType(?string $type): array
    {
        $models = $this->all();

        if ($type === null || $type === '') {
            return $models;
        }

        $type = strtolower($type);

        return array_values(array_filter(
            $models,
            fn (string $modelClass): bool => $modelClass::searchKey() === $type
        ));
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        if ($this->models === []) {
            $this->discover();
        }

        $this->initialized = true;
    }

    private function discover(): void
    {
        foreach (glob(app_path('Models').'/*.php') ?: [] as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            if (is_subclass_of($class, GloballySearchable::class)) {
                $this->register($class);
            }
        }
    }
}
