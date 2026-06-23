<?php

namespace App\Models;

use App\Concerns\InteractsWithGlobalSearch;
use App\Contracts\GloballySearchable;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Project extends Model implements GloballySearchable, Searchable
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, InteractsWithGlobalSearch;

    public string $searchableType = 'Project';

    protected function casts(): array
    {
        return [
            'priority' => ProjectPriority::class,
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    public static function searchKey(): string
    {
        return 'project';
    }

    public static function searchFields(): array
    {
        return ['name', 'description'];
    }

    public function searchTitle(): string
    {
        return $this->name;
    }

    public function toSearchPayload(): array
    {
        return $this->loadMissing([
            'creator:id,name,email,email_verified_at,created_at,updated_at',
            'teams:id,name,display_name,description,created_at,updated_at',
            'tasks:id,project_id,name,description,created_at,updated_at',
        ])->toArray();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creatd_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'project_team');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->searchTitle());
    }
}
