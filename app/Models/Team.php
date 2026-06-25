<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laratrust\Models\Team as LaratrustTeam;

class Team extends LaratrustTeam
{
    use HasFactory;
    public $guarded = [];

    // Relationships
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team', 'team_id', 'project_id', 'id', 'id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id', 'id', 'id');
    }
}
