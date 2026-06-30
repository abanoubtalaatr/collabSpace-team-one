<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\GlobalSearchable;
use App\Enums\UserAvailability;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class User extends Authenticatable implements GlobalSearchable, HasMedia, Searchable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRolesAndPermissions, InteractsWithMedia, Notifiable;

    public const MEDIA_COLLECTION_FILES = 'profile_files';

    public string $searchableType = 'User';

    protected $fillable = [
        'name',
        'email',
        'password',
        'job_title',
        'exp',
        'phone',
        'country_code',
        'about',
        'availability_status',
        'current_team_id',
        'current_project_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return ['name', 'email', 'phone'];
    }

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array
    {
        return [
            'projects:id,created_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            'teams:id,name,display_name,description,created_at,updated_at',
            'tasks:id,project_id,title,description,created_at,updated_at',
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
            'exp' => 'integer',
            'availability_status' => UserAvailability::class,
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_FILES)
            ->useDisk('public');
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

    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_user', 'user_id', 'meeting_id', 'id', 'id');
    }

    public function currentTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function currentProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'current_project_id');
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }

    public function avatarUrl(): ?string
    {
        return $this->getMedia(self::MEDIA_COLLECTION_FILES)
            ->first(fn ($media) => str_starts_with((string) $media->mime_type, 'image/'))
            ?->getFullUrl();
    }
}
