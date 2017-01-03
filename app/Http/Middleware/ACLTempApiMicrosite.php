<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Middleware;

/**
 * Description of ACLTempApiMicrosite
 *
 * @author DESKTOP-BS01
 */

use App\temp_microsite_api;
use Closure;
use App\Http\Middleware\Middleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Route;
class ACLTempApiMicrosite extends Middleware{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action = null)
    {
        return $this->TryCatch(function () use ($request, $next, $action) {
            $microsite = temp_microsite_api::where('app_id', $request->header('app_id', 0))->first();
            if($microsite){
                // Route::current()->setParameter('microsite_id', $microsite->ms_microsite_id);
                return $next($request);
            }else{
                abort(403, "No tiene acceso a la aplicación");
            }
        });
    }
    
    
}