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
        if(session()->has('visit')) {
            $visitData = session()->get('visit');

            if($visitData['ip'] != $request->ip() || $visitData['url'] != $request->url()) {
                $this->logVisit($request);
            }
        } else {
            $this->logVisit($request);
        }

        return $next($request);
    }

    public function logVisit($request) {

        $logHasSaved = false;

        session(['visit' => [
            'ip' => $request->ip(),
            'url' => $request->url(),
        ]]);

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
    }
}
