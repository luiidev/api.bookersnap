<?php

namespace App\Services\Helpers;

use App\res_turn_calendar;
use App\Services\TurnService;
use Carbon\Carbon;
use DB;

class TurnsHelper
{
    public static function TypeTurnForHour(String $date, String $hour, $microsite_id)
    {
        $turn_calendar = res_turn_calendar::select("turn_calendar.*")
            ->from("res_turn_calendar as turn_calendar")
            ->join("res_turn as turn", "turn.id", "=", "turn_calendar.res_turn_id")
            ->where(function ($query) use ($date) {
                $query->where("turn_calendar.start_date", ">=", $date)
                    ->orWhere("turn_calendar.end_date", ">=", $date);
            })
            ->whereRaw("dayofweek(turn_calendar.start_date) = dayofweek(?)", array($date))
            ->whereRaw("? between turn_calendar.start_time and turn_calendar.end_time", array($hour))
            ->where("turn.ms_microsite_id", (int) $microsite_id)
            ->where("turn.status", 1)
            ->orderBy("end_time", "desc")
            ->first();

        if ($turn_calendar !== null) {
            return $turn_calendar->res_turn_id;
        }

        return $turn_calendar;
    }

    public static function TypeTurnWithHourForHour(String $date, String $hour, $microsite_id)
    {
        $turn_format = function ($hour, $turn_id, $type_turn_id) {
            $turn = array();

            $turn["hour"]         = $hour;
            $turn["turn_id"]      = $turn_id;
            $turn["type_turn_id"] = $type_turn_id;

            return (object) $turn;
        };

        $query = res_turn_calendar::select("calendar.*")
            ->from("res_turn_calendar as calendar")
            ->join("res_turn as turn", "turn.id", "=", "calendar.res_turn_id")
            ->where(function ($query) use ($date) {
                $query->where("calendar.start_date", ">=", $date)
                    ->orWhere("calendar.end_date", ">=", $date);
            })
            ->whereRaw("dayofweek(calendar.start_date) = dayofweek(?)", array($date))
            ->where("turn.ms_microsite_id", 1)
            ->where("turn.status", (int) $microsite_id);

        // 1er caso: La hora existe en un turno del calendario
        $case_1   = clone $query;
        $calendar = $case_1->whereRaw("? between calendar.start_time and calendar.end_time", array($hour))->first();

        if ($calendar) {
            return $turn_format($hour, $calendar->res_turn_id, $calendar->res_type_turn_id);
        }

        // No hay turno en la hora que desea reservar

        // 2do caso: Existe un turno previo a la hora que desea reservar
        $case_2   = clone $query;
        $calendar = $case_2->where("calendar.end_time", "<", $hour)->orderBy("end_time", "desc")->first();

        if ($calendar) {
            return $turn_format($calendar->end_time, $calendar->res_turn_id, $calendar->res_type_turn_id);
        }

        // No hay turno en la hora que desea reservar, No existe turno previo a la hora que se desea reservar

        // 3er caso: Existe un turno posterior a la hora que desea reservar
        $case_3   = clone $query;
        $calendar = $case_3->where("calendar.start_time", ">", $hour)->orderBy("start_time", "asc")->first();

        if ($calendar) {
            return $turn_format($calendar->start_time, $calendar->res_turn_id, $calendar->res_type_turn_id);
        }

        // No hay turno en la hora que desea reservar, No existe turno previo a la hora que se desea reservar, No existe un turno posterior

        // 4to caso: devuelve la hora de busqueda y type_turn_id como nulo
        return $turn_format($hour, null, null);
    }
    
    //Obtener el tipo de turno (id) de los turnos
    public function getTypeTurnIdOfTurns(int $microsite_id, array $turns)
    {
        $turnService = new TurnService();
        $typeTurnsId = [];

        foreach ($turns as $key => $value) {
            $turnData      = $turnService->get($microsite_id, $value, "type_turn");
            $typeTurnsId[] = $turnData->typeTurn->id;
        }

        return $typeTurnsId;
    }
    
    /**
     * Sobremesa: Duracion de una reservacion en un turno, por la cantidad de asistentes
     * @param int   $tunr_id    ID del turno
     * 
     */
    public static function sobremesa(int $turn_id, int $guests) {
        $time = "01:30:00";
        $duration = \App\res_turn_time::where("res_turn_id", $turn_id)->where("num_guests", $guests)->first();
        if($duration){
            return $duration->time;
        }
        $duration = \App\res_turn_time::where("res_turn_id", $turn_id)->where("num_guests", "<=" ,$guests)->orderBy("num_guests", "desc")->first();
        if($duration){
            return $duration->time;
        }
        return $time;
    }
    
    /**
     * Colleccion de turnos en una fecha del calendario
     * @param type $microsite_id
     * @return \App\Services\Helpers\collect    [res_turn.*, start_datetime, end_datetime]
     */
    public static function JoinCalendarByDate($microsite_id, $date) {
        $now = Carbon::parse(trim($date));
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $lastTurn = res_turn_calendar::select(array(
                            "res_turn.*",
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
                            DB::raw("IF(end_time > start_time, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("end_datetime")->get();
        return $lastTurn;
    }
    
    /**
     * Colleccion de turnos de los eventos de una fecha
     * @param type $microsite_id
     * @return \App\Services\Helpers\collect    [res_turn.*, start_datetime, end_datetime]
     */
    public static function JoinEventByDate($microsite_id, $date) {
        
        $now = Carbon::parse(trim($date));
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextdate = $now->copy()->addDay()->toDateString();
        $lastTurn = \App\Entities\ev_event::select(array(
                            "res_turn.*",
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
                            DB::raw("IF(hours_end > hours_ini, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
                            DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . "',' ',hours_end), CONCAT('" . $nextday->toDateString() . "',' ',hours_end)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "ev_event.res_turn_id")
                        ->where('datetime_event', '>=', $now->toDateString() . " 00:00:00")
                        ->where('datetime_event', '<', $nextday->toDateString(). " 04:00:00")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->orderBy("end_datetime")->get();
        return $lastTurn;
    }
    
    /**
     * Colleccion de id de turnos de los eventos gratuitos de una fecha
     * @param type $microsite_id
     * @return array   IDS
     */
    public static function IdsTurnEventFreeByDate($microsite_id, $date) {

        $fecha = Carbon::parse($date);
        $datenow = $fecha->toDateString();
        $dayOfWeek = $fecha->dayOfWeek + 1;
        $nextdate = $fecha->copy()->addDay()->toDateString();

//        $turnsEnd = \App\res_turn_calendar::select('res_turn.id', DB::raw("IF(res_turn.hours_ini > res_turn.hours_end, CONCAT('$nextdate', ' ', res_turn.hours_end), CONCAT('$datenow', ' ', res_turn.hours_end)) as  end_datetime"))
//                ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
//                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
//                ->where("res_turn.ms_microsite_id", $microsite_id)
//                ->where("start_date", "<=", $datenow)
//                ->where("end_date", ">=", $datenow)
//                ->orderBy('start_date', 'desc')
//                ->first();
//        
//        $endDate = ($turnsEnd) ? $turnsEnd->end_datetime : $datenow . " 23:59:59";
        $endDate = $nextdate . " 05:00:00";

        $turId = \App\Entities\ev_event::with(["turn" => function($query) use ($datenow, $nextdate) {
                                $query->select('id', 'hours_ini', 'hours_end', 'name', DB::raw("CONCAT('$datenow', ' ', res_turn.hours_ini) as  start_datetime"), DB::raw("IF(res_turn.hours_ini > res_turn.hours_end, CONCAT('$nextdate', ' ', res_turn.hours_end), CONCAT('$datenow', ' ', res_turn.hours_end)) as  end_datetime"));
                            }])->where('status', 1)
                        ->where('datetime_event', '>=', $fecha->toDateString() . " 00:00:00")
                        ->where('datetime_event', '<', $endDate)
                        ->where('bs_type_event_id', 1)
                        ->where('ms_microsite_id', $microsite_id)
                        ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')');
        $turId = $turId->pluck('res_turn_id');        
        return $turId->toArray();
    }
    
    /**
     * Colleccion de turnos activo en el calendario [turno con configuracion en el calendario mayor a la fecha actual]
     * @param type $microsite_id
     * @return \App\Services\Helpers\collect    [res_turn.*, start_datetime, end_datetime]
     */
    public static function JoinCalendarActives($microsite_id) : \Illuminate\Database\Eloquent\Collection {

        $now = Carbon::now();
        $nextday = $now->copy()->addDay();
        $lastTurn = res_turn_calendar::select(array(
                            "res_turn.*",            
                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
                            DB::raw("IF(end_time > start_time, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where(function($query) use($now){
                            return $query->where("start_date", "<=", $now->toDateString())
                                    ->where("end_date", ">=", $now->toDateString())
                                    ->orWhere("start_date", ">=", $now->toDateString());
                        })->orderBy("end_datetime")->get();
        return $lastTurn;
    }
    
    
    /**
     * Colleccion de ids de turnos activo en el calendario [turno con configuracion en el calendario mayor a la fecha actual]
     * @param type $microsite_id
     * @return array   [res_turn.*, start_datetime, end_datetime]
     */
    public static function IdsCalendarActives($microsite_id) {
        $now = Carbon::now();
        $nextday = $now->copy()->addDay();
        $turId = res_turn_calendar::select(array(
                            "res_turn.*",            
//                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
//                            DB::raw("IF(end_time > start_time, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
//                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where(function($query) use($now){
                            return $query->where("start_date", "<=", $now->toDateString())
                                    ->where("end_date", ">=", $now->toDateString())
                                    ->orWhere("start_date", ">=", $now->toDateString());
                        })
                        ->orderBy("end_datetime")->pluck('id');
        return $turId->toArray();
    }
    
    /**
     * Colleccion de turnos activo por eventos [turno de eventos con inicio mayor o igual a la fecha actual]
     * @param type $microsite_id
     * @return \App\Services\Helpers\collect    [res_turn.*, start_datetime, end_datetime]
     */
    public static function JoinEventActives($microsite_id) {
        
        $now = Carbon::now();
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextdate = $now->copy()->addDay()->toDateString();
        $lastTurn = \App\Entities\ev_event::select(array(
                            "res_turn.*",
//                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
                            DB::raw("IF(hours_end > hours_ini, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
                            DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . "',' ',hours_end), CONCAT('" . $nextday->toDateString() . "',' ',hours_end)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "ev_event.res_turn_id")
                        ->where('datetime_event', '>=', $now->toDateString() . " 00:00:00")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->orderBy("end_datetime")->get();
        return $lastTurn;
    }
    
    /**
     * Colleccion de turnos activo por eventos [turno de eventos con inicio mayor o igual a la fecha actual]
     * @param type $microsite_id
     * @return \App\Services\Helpers\collect    [res_turn.*, start_datetime, end_datetime]
     */
    public static function IdsEventActives($microsite_id) {
        
        $now = Carbon::now();
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextdate = $now->copy()->addDay()->toDateString();
        $lastTurn = \App\Entities\ev_event::select(array(
                            "res_turn.*",
//                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
                            DB::raw("IF(hours_end > hours_ini, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
                            DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . "',' ',hours_end), CONCAT('" . $nextday->toDateString() . "',' ',hours_end)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "ev_event.res_turn_id")
                        ->where('datetime_event', '>=', $now->toDateString() . " 00:00:00")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->orderBy("end_datetime")->pluck('id');
        return $lastTurn->toArray;
    }
    
    
    
}
