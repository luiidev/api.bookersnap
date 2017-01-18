<?php

namespace App\Http\Middleware;

use App\Services\Helpers\PrivilegeHelper;
use Closure;

class AclMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action = null)
    {
        $privileges =  PrivilegeHelper::getPrivileges($request->_bs_user_id, $request->route("microsite_id"), 2);

        if (count($privileges) > 0) {
            if ( in_array($action, $privileges) ) {
                return $next($request);
            }
        } 

        return response()->json("unauthorized", 401);
    }
}
