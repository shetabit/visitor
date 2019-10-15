<?php

namespace Shetabit\Visitor\Provider;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Shetabit\Visitor\Visitor;

class VisitorServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../config/visitor.php' => config_path('visitor.php'),
            ],
            'config'
        );

        /**
         * Migrations that needs to be done by user.
         */
        $this->publishes(
            [
                __DIR__.'/../../database/migrations/' => database_path('migrations')
            ],
            'migrations'
        );

        $this->registerMacroHelpers();
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        /**
         * Load default configurations.
         */
        $this->mergeConfigFrom(
            __DIR__.'/../../config/visitor.php', 'visitor'
        );

        /**
         * Bind to service container.
         */
        $this->app->singleton('shetabit-visitor', function () {
            $request = app(Request::class);

            return new Visitor($request, config('visitor'));
        });
    }

    /**
     * Register micros
     */
    protected function registerMacroHelpers()
    {
        Request::macro('visitor', function () {
            return app('shetabit-visitor');
        });
    }
}
