<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\GlobalSearchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ReflectionClass;
use Throwable;

class GlobalSearchModelRegistry
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {}

    /**
     * @return array<int, class-string<Model&GlobalSearchable>>
     */
    public function searchableModels(): array
    {
        $modelsPath = app_path('Models');

        if (! $this->filesystem->isDirectory($modelsPath)) {
            return [];
        }

        $models = [];

        foreach ($this->filesystem->allFiles($modelsPath) as $file) {
            $class = $this->classFromPath($file->getPathname(), $modelsPath);

            if ($class !== null && $this->isSearchableModel($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * @return array<int, class-string<Model&GlobalSearchable>>
     */
    public function resolveModels(?string $type): array
    {
        $models = $this->searchableModels();
        $type = strtolower(trim((string) $type));

        if ($type === '') {
            return $models;
        }

        return array_values(array_filter(
            $models,
            fn (string $model): bool => strtolower($model::globalSearchType()) === $type
        ));
    }

    private function classFromPath(string $path, string $modelsPath): ?string
    {
        $relativePath = Str::of($path)
            ->after($modelsPath.DIRECTORY_SEPARATOR)
            ->replace(DIRECTORY_SEPARATOR, '\\')
            ->replaceLast('.php', '')
            ->toString();

        return $relativePath === '' ? null : "App\\Models\\{$relativePath}";
    }

    /**
     * @param  class-string  $class
     */
    private function isSearchableModel(string $class): bool
    {
        try {
            if (! class_exists($class)) {
                return false;
            }

            $reflection = new ReflectionClass($class);

            return ! $reflection->isAbstract()
                && $reflection->isSubclassOf(Model::class)
                && $reflection->implementsInterface(GlobalSearchable::class);
        } catch (Throwable) {
            return false;
        }
    }
}
