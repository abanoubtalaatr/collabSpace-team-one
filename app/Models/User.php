<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\InteractsWithGlobalSearch;
use App\Contracts\GloballySearchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements GloballySearchable, Searchable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRolesAndPermissions, InteractsWithGlobalSearch, Notifiable;

    public string $searchableType = 'User';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static function searchKey(): string
    {
        return 'user';
    }

    public static function searchFields(): array
    {
        return ['name', 'email'];
    }

    public function searchTitle(): string
    {
        return $this->name;
    }

    public function toSearchPayload(): array
    {
        return $this->loadMissing([
            'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            'teams:id,name,display_name,description,created_at,updated_at',
            'tasks:id,project_id,name,description,created_at,updated_at',
            'roles:id,name,display_name,description,created_at,updated_at',
        ])->toArray();
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'creatd_by');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_user');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->searchTitle());
    }
}
