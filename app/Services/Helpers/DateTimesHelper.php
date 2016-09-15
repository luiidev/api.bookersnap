<?php

namespace App\Services\Helpers;

use Carbon\Carbon;

class DateTimesHelper
{

    /**
     * Comparacion de tiempos para validar si el tiempo se cruza con el otro
     * @param  String|Time $start_time  Tiempo de inicio de referencia
     * @param  String|Time $end_time    Tiempo final de referecnia
     * @param  String|Time $start_point Tiempo de inicio a validar
     * @param  String|Time $end_point   Tiempo final a validar
     * @param  String|Date $date        Fecha de referencia
     * @return Boolean              Falso: El tiempo no se cruza, True: Los tiempos se cruzan
     */
    public static function compareTimes($start_time, $end_time, $start_point, $end_point, $date, $response = false)
    {

        $time = time();
                   
        $_start_time = strtotime($start_time, $time);
        $_end_time = strtotime($end_time, $time);

        $start_time = Carbon::parse($date.' '.$start_time);
        $end_time = Carbon::parse($date.' '.$end_time);

        if ($_end_time < $_start_time) {
            $end_time->addDay();
        }

        $_start_point = strtotime($start_point, $time);
        $_end_point = strtotime($end_point, $time);

        $start_point = Carbon::parse($date.' '.$start_point);
        $end_point = Carbon::parse($date.' '.$end_point);

        if ($_end_point < $_start_point) {
            $end_point ->addDay();
        }

        $message = "";
        $validate = (object)array("fail" => false, "message" => "");
        if ($start_point->between($start_time, $end_time, false)) {
            $message = "La hora de inicio genera conflicto con otro turno, no es posible el cambio de turno.";
        } elseif ($end_point->between($start_time, $end_time, false)) {
            $message = "La hora de fin genera conflicto con otro turno, no es posible el cambio de turno.";
        } elseif ($start_point->lt($start_time) && $end_point->gt($end_time)) {
            $message = "El horario genera conflicto con otro turno, no es posible el cambio de turno.";
        }

        if ($message  !== "" && !$response)  {
            $validate->fail = true;
            $validate->message = $message;
            
            return $validate;
        } else if ($message  !== "" && $response) {
            abort(421, $message);
        } else {
            return $validate;
        }
    }
}
