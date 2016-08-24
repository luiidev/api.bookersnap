<?php

namespace App\Http\Middleware;

use Closure;

class AclMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action = null) {
        
        $request['_bs_user_id'] = 1;
        return $next($request);
        
    }

}
