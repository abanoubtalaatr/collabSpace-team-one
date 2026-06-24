<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Concerns\Filterable;

class Project extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    use Filterable;           

   
    const MEDIA_COLLECTION_ATTACHMENTS = 'attachments';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'deadline',
        'priority',
        'status',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'start_date' => 'date',
        'deadline'   => 'date',
        'priority' => ProjectPriority::class,
        'status' => ProjectStatus::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team');
    }*/

    /*
    |--------------------------------------------------------------------------
    | Media Library
    |--------------------------------------------------------------------------
    */

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::MEDIA_COLLECTION_ATTACHMENTS)
             ->useDisk('public');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /*public function scopeForTeamMember($query, int $userId)
    {
        return $query->whereHas('teams.users', fn ($q) => $q->where('users.id', $userId));
    }*/

    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public  function priorities(): array
    {
        return ProjectPriority::values();
    }

    public  function statuses(): array
    {
        return ProjectStatus::values();
    }



   

}
