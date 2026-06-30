<?php

namespace App\Models;

use App\Concerns\Filterable;
use App\Contracts\GlobalSearchable;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Searchable\Searchable;
use Spatie\Searchable\SearchResult;

class Project extends Model implements GlobalSearchable, HasMedia, Searchable
{
    /** @use HasFactory<ProjectFactory> */
    use Filterable, HasFactory, InteractsWithMedia;

    protected $table = 'projects';

    public const MEDIA_COLLECTION_ATTACHMENTS = 'attachments';

    public string $searchableType = 'Project';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'deadline',
        'priority',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'deadline' => 'date',
        'priority' => ProjectPriority::class,
        'status' => ProjectStatus::class,
    ];

    /**
     * @return array<int, string>
     */
    public static function globalSearchColumns(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return array<int, string>
     */
    public static function globalSearchRelations(): array
    {
        return [
            'creator:id,name,email,email_verified_at,created_at,updated_at',
            'teams:id,name,display_name,description,created_at,updated_at',
            'tasks:id,project_id,name,description,created_at,updated_at',
        ];
    }

    public static function globalSearchType(): string
    {
        return 'project';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'attachable');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_ATTACHMENTS)
            ->useDisk('public');
    }

    public function scopeForTeamMember($query, int $userId)
    {
        return $query->whereHas('teams.members', fn ($q) => $q->where('users.id', $userId));
    }

    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function priorities(): array
    {
        return ProjectPriority::values();
    }

    public function statuses(): array
    {
        return ProjectStatus::values();
    }

    public function getSearchResult(): SearchResult
    {
        return new SearchResult($this, $this->name);
    }
}
