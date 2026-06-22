<?php

namespace App\Models;

use Laratrust\Models\Team as LaratrustTeam;

class Team extends LaratrustTeam
{
    public $guarded = [];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_team');
    }
}
