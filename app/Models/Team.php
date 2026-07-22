<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\GlobalSearchable;
use Database\Factories\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laratrust\Models\Team as LaratrustTeam;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Team extends LaratrustTeam implements GlobalSearchable, Searchable
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    public $guarded = [];

    public string $searchableType = 'Team';

    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return ['name'];
    }

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array
    {
        // Avoid constrained select lists that can break Spatie search with SQL errors.
        return [
            'members:id,name,email',
            'projects:id,name,status',
        ];
    }

    public static function globalSearchType(): string
    {
        return 'team';
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team', 'team_id', 'project_id', 'id', 'id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id', 'id', 'id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
