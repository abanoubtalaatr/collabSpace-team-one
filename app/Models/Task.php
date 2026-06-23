<?php

namespace App\Models;

use App\Concerns\InteractsWithGlobalSearch;
use App\Contracts\GloballySearchable;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Task extends Model implements GloballySearchable, Searchable
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, InteractsWithGlobalSearch;

    public string $searchableType = 'Task';

    protected function casts(): array
    {
        return [];
    }

    public static function searchKey(): string
    {
        return 'task';
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
            'project:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            'users:id,name,email,email_verified_at,created_at,updated_at',
        ])->toArray();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->searchTitle());
    }
}
