<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model implements Searchable
{
    protected function casts(): array
    {
        return [
            'priority' => ProjectPriority::class,
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'deadline' => 'date',
        ];
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creatd_by', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team', 'project_id', 'team_id', 'id', 'id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
