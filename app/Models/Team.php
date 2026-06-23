<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laratrust\Models\Team as LaratrustTeam;

class Team extends LaratrustTeam
{
    public $guarded = [];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team', 'team_id', 'project_id', 'id', 'id');
    }
}
