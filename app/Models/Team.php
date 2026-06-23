<?php

declare(strict_types=1);

namespace App\Models;

use Laratrust\Models\Team as LaratrustTeam;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Team extends LaratrustTeam implements Searchable
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    public $guarded = [];

    public string $searchableType = 'Team';

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team', 'team_id', 'project_id', 'id', 'id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
