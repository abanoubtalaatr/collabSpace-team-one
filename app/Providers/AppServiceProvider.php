<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $default = config('broadcasting.default');

        if ($default === 'pusher' && empty(config('broadcasting.connections.pusher.key'))) {
            config(['broadcasting.default' => 'log']);
        }

        if ($default === 'reverb' && empty(config('broadcasting.connections.reverb.key'))) {
            config(['broadcasting.default' => 'log']);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'project' => Project::class,
            'task' => Task::class,
            'team' => Team::class,
            'role' => Role::class,
            'file' => File::class,
        ]);
    }
}
