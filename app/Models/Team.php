<?php

namespace App\Models;

use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laratrust\Models\Team as LaratrustTeam;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Team extends LaratrustTeam implements Searchable
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    public $guarded = [];

    public string $searchableType = 'Team';

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_team');
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
