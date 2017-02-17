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
            return response()->json(["unauthorized :(", $token], 401);
            if (!$token) {
                return response()->json("unauthorized :)", 401);
            }
            if ($jwt = JWTAuth::decode($token)->get()) {
                $user_id = AuthHelper::getSession($jwt["aud"]);
                if (! is_null($user_id) ) {
                    $request->request->set("_bs_user_id", $user_id);
                    $request->request->set("_bs_user_type_root", $jwt["type_root"]);
                    return $next($request);
                }
            }
        } 
        catch (TokenExpiredException $e) {}
        catch (TokenInvalidException $e) {}
        catch (JWTException $e) {}

        return response()->json(["unauthorized :("], 401);
    }
}
