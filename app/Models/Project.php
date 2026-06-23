<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;
    protected $fillable = [
        'created_by',
        'name',
        'description',
        'start_date',
        'deadline',
        'priority',
        'status'
    ];

    // Define the relationship with the User model
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Define the relationship with the Task model
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Define the relationship with the Team model
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'project_team');
    }
}
