<?php

namespace App\Http\Middleware;

use Closure;
use App\Entities\ms_microsite;
use Carbon\Carbon;

class SetTimeZoneMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        
//        $microsite = ms_microsite::find($request->route("microsite_id"));
        
//        $value1 = config('app.timezone');
//        config(['app.timezone' => 'America/Chicago']);
//        $value2 = config('app.timezone');        
//        if ($microsite) {            
//            $horario = (int) ($microsite->map_longitude / 15);
//            if ($microsite->map_longitude % 15 != 0) {
//                $horario ++;
//            }
//            $confitimezoneIndex = "timezone.".$horario;
//            $timezoneName = config($confitimezoneIndex);
//            dd($timezoneName);
//            if($timezoneName){
//                date_default_timezone_set($timezoneName);
//            }            
//            $request["timezone"] = date_default_timezone_get();
//            $date = Carbon::now();
//            dd($microsite->map_longitude, $horario, date_default_timezone_get(), $date);
//            
//        } else {
//            $request["timezone"] = "America/Lima";
//            date_default_timezone_set('America/Lima');
//        }
//        dd($microsite->map_longitude);
        $request["timezone"] = "America/Lima";
        date_default_timezone_set('America/Lima');
//        $request["timezone"] = "America/Mexico_City";
//        date_default_timezone_set('America/Mexico_City');


        return $next($request);
    }
    
    function getTimeZoneByLongitude($longitude) {
        $horario = (int) ($microsite->map_longitude / 15);
        if ($microsite->map_longitude % 15 != 0) {
            $horario ++;
        }
        switch ($horario){
            case -4:
                return "America/Lima";                
            case -5:
                return "America/Mexico_City";
            default : 
                return "America/Mexico_City";
        }
    }

}
