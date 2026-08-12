<?php

namespace Shetabit\Visitor\Provider;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Shetabit\Visitor\Facade\Visitor as VisitorFacade;
use Shetabit\Visitor\Visitor;

class VisitorServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        /**
         * Configurations that needs to be done by user.
         */
        $this->publishes(
            [
                $this->packagePath('config/visitor.php') => config_path('visitor.php'),
            ],
            ['config', 'visitor-config']
        );

        /**
         * Migrations of the table the visits are logged in.
         *
         * The file names carry the order the migrations have to run in, which
         * `vendor:publish` keeps.
         */
        $this->publishesMigrations(
            [
                $this->packagePath('database/migrations') => database_path('migrations'),
            ],
            ['migrations', 'visitor-migrations']
        );

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
        $this->mergeConfigFrom($this->packagePath('config/visitor.php'), 'visitor');

        /**
         * Bind to service container.
         *
         * The visitor is handed the request it works with while it is being
         * built, so it is bound for the length of one request: a `singleton`
         * would keep answering with the data of the first request a long
         * running worker (Octane, a queue worker) handed it.
         */
        $this->app->scoped(
            VisitorFacade::SERVICE_NAME,
            static fn (): Visitor => new Visitor(app(Request::class), (array) config('visitor', []))
        );

        // Allow the visitor to be resolved and injected by its class name too
        $this->app->alias(VisitorFacade::SERVICE_NAME, Visitor::class);
    }

    /**
     * Register micros
     */
    protected function registerMacroHelpers(): void
    {
        Request::macro('visitor', fn (): Visitor => visitor());
    }

    /**
     * The absolute path of the given file of the package.
     */
    private function packagePath(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }
}
