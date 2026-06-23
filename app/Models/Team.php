<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'display_name',
        'description'
    ];

    // Define the relationship with the User model
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    // Define the relationship with the Project model
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team');
    }
}
