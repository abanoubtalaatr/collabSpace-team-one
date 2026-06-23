<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureCommands();
        $this->configureModels();
        $this->configureUrl();
        $this->relationEnforceMorphMap();
    }

    private function configureCommands()
    {
        DB::prohibitDestructiveCommands(
            app()->isProduction()
        );
    }

    private function configureModels()
    {
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();
    }

    private function configureUrl()
    {
        URL::forceHttps(app()->isProduction());
    }

    private function relationEnforceMorphMap()
    {
        Relation::enforceMorphMap([
            'user' => User::class,
        ]);
    }
}
