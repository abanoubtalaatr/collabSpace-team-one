<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    protected $table = 'tasks';

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'completion_rate',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'completion_rate' => 'integer',
        ];
    }

    // Relationships
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id', 'id', 'id');
    }
}
