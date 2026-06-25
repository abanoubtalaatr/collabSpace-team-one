<?php

namespace App\Models;

use App\Concerns\Filterable;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Project extends Model implements HasMedia
{
    use Filterable, HasFactory, InteractsWithMedia;

    protected $table = 'projects';

    public const MEDIA_COLLECTION_ATTACHMENTS = 'attachments';

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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team');
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
}
