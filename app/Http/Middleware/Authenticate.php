<?php

namespace App\Http\Middleware;

use App\Services\Helpers\AuthHelper;
use Closure;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        try {
            $token = JWTAuth::getToken();
            // return response()->json($token);
            // return response()->json($request->header());
            if ($jwt = JWTAuth::decode($token)->get()) {
                $user_id = AuthHelper::getSession($jwt["aud"]);
                if (! is_null($user_id) ) {
                    $request->_bs_user_id = $user_id;
                    return $next($request);
                }
            }
        } catch (TokenExpiredException $e) {
            // return redirect()->guest(route('microsite-login'));
        } catch (TokenInvalidException $e) {
            // return redirect()->guest(route('microsite-login'));
        } catch (JWTException $e) {
            // return redirect()->guest(route('microsite-login'));
        }

        return response()->json(["unauthorized"], 401);
    }
}
