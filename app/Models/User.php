<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Contracts\GlobalSearchable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements GlobalSearchable, Searchable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRolesAndPermissions, Notifiable;

    public string $searchableType = 'User';

    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return ['name', 'email'];
    }

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array
    {
        return [
            'projects:id,created_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            'teams:id,name,display_name,description,created_at,updated_at',
            'tasks:id,project_id,name,description,created_at,updated_at',
            'roles:id,name,display_name,description,created_at,updated_at',
        ];
    }

    public static function globalSearchType(): string
    {
        return 'user';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'created_by', 'id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user', 'user_id', 'team_id', 'id', 'id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id', 'id', 'id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_user', 'user_id', 'conversation_id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
