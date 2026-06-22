<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Models\Role as RoleModel;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Role extends RoleModel implements Searchable
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public $guarded = [];

    public string $searchableType = 'Role';

    public function users(): MorphToMany
    {
        return $this->getMorphByUserRelation('users');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
