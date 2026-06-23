<?php

namespace App\Models;

use App\Concerns\InteractsWithGlobalSearch;
use App\Contracts\GloballySearchable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laratrust\Models\Team as LaratrustTeam;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Team extends LaratrustTeam implements GloballySearchable, Searchable
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory, InteractsWithGlobalSearch;

    public $guarded = [];

    public string $searchableType = 'Team';

    public static function searchKey(): string
    {
        return 'team';
    }

    public static function searchFields(): array
    {
        return ['name'];
    }

    public function searchTitle(): string
    {
        return $this->name;
    }

    public function toSearchPayload(): array
    {
        return $this->loadMissing([
            'members:id,name,email,email_verified_at,created_at,updated_at',
            'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
        ])->toArray();
    }

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
        return new SearchResult($this, $this->searchTitle());
    }
}
