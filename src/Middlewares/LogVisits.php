<?php

namespace Shetabit\Visitor\Middlewares;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LogVisits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $logHasSaved = false;

        // create log for first binded model
        foreach ($request->route()->parameters() as $parameter) {
            if ($parameter instanceof Model) {
                visitor()->visit($parameter);

                $logHasSaved = true;

                break;
            }
        }

        // create log for normal visits
        if (!$logHasSaved) {
            visitor()->visit();
        }

        return $next($request);
    }
}
