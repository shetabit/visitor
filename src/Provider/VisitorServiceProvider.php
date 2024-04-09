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
    public function boot(): void
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

        if (! class_exists('CreateVisitsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/../../database/migrations/create_visits_table.php.stub' => database_path("/migrations/{$timestamp}_create_visits_table.php"),
            ], 'migrations');
        }


        $this->registerMacroHelpers();
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        /**
         * Load default configurations.
         */
        $this->mergeConfigFrom(__DIR__.'/../../config/visitor.php', 'visitor');

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
    protected function registerMacroHelpers(): void
    {
        Request::macro('visitor', function () {
            return app('shetabit-visitor');
        });
    }
}
