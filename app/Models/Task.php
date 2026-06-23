<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model implements Searchable
{
    protected function casts(): array
    {
        return [];
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id', 'id', 'id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
