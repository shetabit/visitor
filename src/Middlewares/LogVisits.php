<?php

namespace Shetabit\Visitor\Middlewares;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogVisits
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->log($request);

        return $next($request);
    }

    /**
     * Log the visit of the first model the router bound, or of the page.
     */
    private function log(Request $request): void
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                visitor()->visit($parameter);

                return;
            }
        }

        visitor()->visit();
    }
}
