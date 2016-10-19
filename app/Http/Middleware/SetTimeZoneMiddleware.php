<?php

namespace App\Http\Middleware;

use Closure;

class SetTimeZoneMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request["timezone"] = "America/Mexico_City";
        return $next($request);
    }
}
