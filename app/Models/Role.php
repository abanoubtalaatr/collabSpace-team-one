<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\GlobalSearchable;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Models\Role as RoleModel;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Role extends RoleModel implements GlobalSearchable, Searchable
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public $guarded = [];

    public string $searchableType = 'Role';

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
        return [
            'users:id,name,email,email_verified_at,created_at,updated_at',
        ];
    }

    public static function globalSearchType(): string
    {
        return 'role';
    }

    public function users(): MorphToMany
    {
        return $this->getMorphByUserRelation('users');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
