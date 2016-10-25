<?php

namespace App\Services\Helpers;

use App\res_turn_calendar;

class TurnsHelper
{
    public static function TypeTurnForHour(String $date, String $hour, $microsite_id)
    {
        $calendar = res_turn_calendar::select("calendar.*")
                ->from("res_turn_calendar as calendar")
                ->join("res_turn as turn", "turn.id", "=", "calendar.res_turn_id")
                ->where(function($query) use ($date){
                    $query->where("calendar.start_date", ">=", $date)
                                ->orWhere("calendar.end_date", ">=", $date);
                })
                ->whereRaw("dayofweek(calendar.start_date) = dayofweek(?)", array($date))
                ->whereRaw("? between calendar.start_time and calendar.end_time", array($hour))
                ->where("turn.ms_microsite_id", 1)
                ->where("turn.status", (int) $microsite_id)
                ->first();

        if ($calendar !== null) {
            return $calendar->res_type_turn_id;
        }
        
        return $calendar;
    }

    public static function TypeTurnWithHourForHour(String $date, String $hour, $microsite_id)
    {
        $turn_format = function($hour, $type_turn_id) {
            $turn = array();

            $turn["hour"] = $hour;
            $turn["type_turn_id"] = $type_turn_id;

            return (object) $turn;
        };

        $query = res_turn_calendar::select("calendar.*")
                ->from("res_turn_calendar as calendar")
                ->join("res_turn as turn", "turn.id", "=", "calendar.res_turn_id")
                ->where(function($query) use ($date){
                    $query->where("calendar.start_date", ">=", $date)
                                ->orWhere("calendar.end_date", ">=", $date);
                })
                ->whereRaw("dayofweek(calendar.start_date) = dayofweek(?)", array($date))
                ->where("turn.ms_microsite_id", 1)
                ->where("turn.status", (int) $microsite_id);

        // 1er caso: La hora existe en un turno del calendario
        $case_1 = clone $query;
        $calendar = $case_1->whereRaw("? between calendar.start_time and calendar.end_time", array($hour))->first();

        if ($calendar) return $turn_format($hour, $calendar->res_type_turn_id);

        // No hay turno en la hora que desea reservar

        // 2do caso: Existe un turno previo a la hora que desea reservar
        $case_2 = clone $query;
        $calendar = $case_2->where("calendar.end_time", "<", $hour)->orderBy("end_time", "desc")->first();

        if ($calendar) return $turn_format($calendar->end_time, $calendar->res_type_turn_id);

        // No hay turno en la hora que desea reservar, No existe turno previo a la hora que se desea reservar

        // 2do caso: Existe un turno posterior a la hora que desea reservar
        $case_3 = clone $query;
        $calendar = $case_3->where("calendar.start_time", ">", $hour)->orderBy("start_time", "asc")->first();

        if ($calendar) return $turn_format($calendar->start_time, $calendar->res_type_turn_id);

        // No hay turno en la hora que desea reservar, No existe turno previo a la hora que se desea reservar, No existe un turno posterior

        // 4to caso: devuelve la hora de buesqueda y type_turn_id como nulo
        return $turn_format($hour, null);
    }
}