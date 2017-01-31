<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Middleware;

/**
 * Description of ACLTempUserSession
 *
 * @author DESKTOP-BS01
 */
use App\OldTokenSession;
use Closure;
use App\Http\Middleware\Middleware;

class ACLTempUserSesion extends Middleware{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action = null) {
        
        return $this->TryCatch(function () use ($request, $next, $action) {
            
            $token_session = OldTokenSession::where('token', $request->header('TOKEN'))->first();
            
            if($token_session){
                $request->request->set('__ID_USER', $token_session->usermicrosite_id);
                $request->request->set('__ID_SESSION', $token_session->token);
                return $next($request);
            }else{
                abort(403, trans('messages.forbidden'));
            }
        });
        
    }
    
    
}