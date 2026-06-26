<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Meeting extends Model
{
    protected $table = 'meetings';

    protected $fillable = [
        'title',
        'description',
        'scheduled_at',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_user', 'meeting_id', 'user_id', 'id', 'id');
    }
}
