<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'project' => \App\Models\Project::class,
            // لو عندك models تانية بتستخدم media أو morphs، ضيفهم هنا
            // 'user'    => \App\Models\User::class,
            // 'task'    => \App\Models\Task::class,
        ]);
    }
}
