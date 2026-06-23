<?php

namespace App\Models;

use App\Concerns\InteractsWithGlobalSearch;
use App\Contracts\GloballySearchable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Models\Role as RoleModel;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Role extends RoleModel implements GloballySearchable, Searchable
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory, InteractsWithGlobalSearch;

    public $guarded = [];

    public string $searchableType = 'Role';

    public static function searchKey(): string
    {
        return 'role';
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
            'users:id,name,email,email_verified_at,created_at,updated_at',
        ])->toArray();
    }

    public function users(): MorphToMany
    {
        return $this->getMorphByUserRelation('users');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->searchTitle());
    }
}
