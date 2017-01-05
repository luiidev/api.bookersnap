<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Helpers;

/**
 * Description of CalendarHelper
 *
 * @author DESKTOP-BS01
 */
use App\res_turn_calendar;
use Carbon\Carbon;
use DB;
use App\res_turn;
use App\Entities\ev_event;

class CalendarHelper {

    /**
     * Busca disponibilidad de una mesa en un hora determinada
     * @param  int                  $microsite_id id del microsito a buscarreturn "test"
     * @param  string $date        fecha de la reservacion
     * @return array               ['datetimeOpen' => DATETIME, 'datetimeClose' => DATETIME]
     */
    static function realDateTimeOpenAndClose(int $microsite_id, string $date = null) {

        $now = is_null($date) ? CalendarHelper::realDate($microsite_id) : Carbon::parse($date);
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextday = $now->copy()->addDay();

        $turn = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn.res_type_turn_id",
                            "res_turn.name",
//                            DB::raw("dayofweek(res_turn_calendar.start_date) as dayOfWeek"),            
//                            DB::raw("IF(start_date < '$date', "
//                                    . "ADDDATE('$date', INTERVAL IF(dayofweek(start_date) >= $dayOfWeek, (dayofweek(start_date) - $dayOfWeek), 7 + (dayofweek(start_date)-$dayOfWeek)) DAY), "
//                                    . "start_date) AS date_ini"),
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("start_datetime", 'ASC')->get();
        $count = $turn->count();
        $firstTurn = $turn->first();
        $datetimeOpen = $now->toDateString() . " 00:00:00";
        $datetimeClose = $datetimeOpen . " 23:59:59";
        if ($count > 0) {
            $datetimeOpen = $firstTurn->start_datetime;
            $datetimeClose = $firstTurn->end_datetime;
            if ($count > 1) {
                $lastTurn = $turn->last();
                $datetimeClose = $lastTurn->end_datetime;
            }
        }
        return [$datetimeOpen, $datetimeClose];
    }

    /**
     * Busca disponibilidad de una mesa en un hora determinada
     * @param  int                  $microsite_id id del microsito a buscarreturn "test"
     * @param  string $date        fecha de la reservacion
     * @return array               ['datetimeOpen' => DATETIME, 'datetimeClose' => DATETIME]
     */
    static function realDateTimeOpen(int $microsite_id, $date) {

        $now = Carbon::parse($date);
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextday = $now->copy()->addDay();

        $turn = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn.res_type_turn_id",
                            "res_turn.name",
//                            DB::raw("dayofweek(res_turn_calendar.start_date) as dayOfWeek"),            
//                            DB::raw("IF(start_date < '$date', "
//                                    . "ADDDATE('$date', INTERVAL IF(dayofweek(start_date) >= $dayOfWeek, (dayofweek(start_date) - $dayOfWeek), 7 + (dayofweek(start_date)-$dayOfWeek)) DAY), "
//                                    . "start_date) AS date_ini"),
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("start_datetime", 'ASC')->limit(1)->first();

        return ($turn) ? $turn->start_datetime : $now->toDateTimeString();
    }

    static function realDateTimeClose(int $microsite_id, $date) {

        $now = Carbon::parse($date);
        $dayOfWeek = $now->dayOfWeek + 1;
        $nextday = $now->copy()->addDay();

        $turn = res_turn_calendar::select(array(
                            "res_turn.ms_microsite_id",
                            "res_turn.id",
                            "res_turn.res_type_turn_id",
                            "res_turn.name",
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("end_datetime", 'DESC')->limit(1)->first();

        return ($turn) ? $turn->end_datetime : $now->toDateTimeString();
    }

    /**
     * Fecha de aperturas del lugar segun la hora actual.
     * @param  int    $microsite_id     id del microsito
     * @return string                    
     */
    static function realDate(int $microsite_id, $date = null) {

        $now = Carbon::now();
        if (strcmp($date, $now->toDateString()) == 0 || is_null($date)) {
            $yesterday = $now->copy()->yesterday();

            $turnYesterday = res_turn_calendar::fromMicrosite($microsite_id, $yesterday->toDateString())
                    ->where(DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time))"), ">=", $now->toDateTimeString())
                    ->count();

            if ($turnYesterday > 0) {
                return $yesterday->toDateString();
            }

            $turnYesterday = res_turn::inEventFree($yesterday->toDateString(), $yesterday->toDateString())->where('ms_microsite_id', $microsite_id)
                    ->where(DB::raw("IF(hours_end > hours_ini, CONCAT('" . $yesterday->toDateString() . " ', hours_ini), CONCAT('" . $now->toDateString() . " ',hours_end))"), ">=", $now->toDateTimeString())
                    ->count();
            if ($turnYesterday > 0) {
                return $yesterday->toDateString();
            }

            return $now->toDateString();
        }
        return $date;
    }

    static function dateReservationInCalendar(int $microsite_id, string $date, string $time) {

        $now = Carbon::parse(trim($date) . " " . trim($time));
        $yesterday = $now->copy()->subDay();
        $dayOfWeek = $yesterday->dayOfWeek + 1;

        $turnYesterday = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
//                            DB::raw("dayofweek(res_turn_calendar.start_date) as dayOfWeek"),
                            DB::raw("CONCAT('" . $yesterday->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $yesterday->toDateString())
                        ->where("end_date", ">=", $yesterday->toDateString())
                        ->where(DB::raw("CONCAT('" . $yesterday->toDateString() . "',' ',start_time)"), '<=', $now->toDateTimeString())
                        ->where(DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time))"), ">=", $now->toDateTimeString())
                        ->orderBy("end_datetime", 'DESC')->limit(1)->first();

        if ($turnYesterday) {
            return $yesterday->toDateString();
        }
        return $now->toDateString();
    }

    /**
     * Busca la fecha de apertura mas proxima
     * @param  int    $microsite_id     id del microsito a buscar
     * @param  string $date             fecha de inicio de busqueda
     * @return array                    Carbon
     */
    static function searchDate(int $microsite_id, string $date = null) {
        
        $datenow = Carbon::now();        
        $yesterday = $datenow->copy()->subDay();
        $dateyesterday = $yesterday->toDateString();
        $nextday = $datenow->copy()->addDay();
        $datenextday = $nextday->toDateString();
                
        $dateIni = $date;
        $date = (strcmp($date, $datenow->toDateString()) == -1 || is_null($date))? $datenow->toDateString():$date;
        
        if(is_null($dateIni) || strcmp($dateIni, $yesterday->toDateString()) == 0){  // SI ES LA FEHCA DE HOY BUSCAR HORARIOS DE MADRUGADA DE AYER
            
            $datetimeEnd = "CONCAT('$date ', end_time)";            
            /* turnos de Ayer en el calendario */
            $lastTurn = res_turn_calendar::fromMicrosite($microsite_id, $dateyesterday, $dateyesterday)->orderBy('start_date');
            $lastTurn = $lastTurn->whereRaw("start_time > end_time")->whereRaw("$datetimeEnd >= ?", [$datenow->toDateTimeString()]);
            $lastTurn = $lastTurn->get();            
            /* turnos de Ayer en eventos */
            $eventFree = ev_event::eventFreeActive($dateyesterday, $dateyesterday)->select('*', DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"));
            $eventFree = $eventFree->where('ms_microsite_id', $microsite_id)->whereHas('turn', function($query) use($date, $datenow){
                $datetimeEnd = "CONCAT('$date ', hours_end)";
                return $query->whereRaw("hours_ini > hours_end")->whereRaw("$datetimeEnd >= ?", [$datenow->toDateTimeString()]);
            })->with(['turn'])->whereRaw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') = ?", [$dateyesterday])->orderBy('datetime_event')->get();
            
            if($lastTurn->count() >0 || $eventFree->count() > 0){
                return $yesterday;
            }
        }
        
        // Buscar el turno en la fecha.
        $datetimeEnd = "IF(end_time > start_time ,CONCAT('$date ', end_time), CONCAT('$datenextday ', end_time))";  
        $turncalendar = res_turn_calendar::fromMicrosite($microsite_id, $date, $date)->select("*", DB::raw("$datetimeEnd AS datetime_end"), DB::raw("'$date' AS start_date"))->whereRaw("$datetimeEnd >= ?",[$datenow->toDateTimeString()])->orderBy('datetime_end')->first();
        
        if(!$turncalendar){
            // Buscar el turno mas proximos.
            $turncalendar = res_turn_calendar::fromMicrositeActives($microsite_id)->orderBy('start_date')->first();            
        }        
        // Buscar el turno de eventos en la fecha.
        $eventFree = ev_event::eventFreeActive($date, $date)->select('*', DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"))->where('ms_microsite_id', $microsite_id)->with('turn')->orderBy('datetime_event')->first();
        if(!$eventFree){
            // Buscar el turno de eventos mas proximos.
            $eventFree = ev_event::eventFreeActive()->select('*', DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"))->where('ms_microsite_id', $microsite_id)->with('turn')->orderBy('datetime_event')->first();
        }
        
        if($turncalendar || $eventFree){
            $dateSearch = Carbon::parse($date);
            
            if($turncalendar && $eventFree){
                $dateCalendar = Carbon::parse($turncalendar->start_date);
                $diffCal = $dateSearch->diffInDays($dateCalendar);                
                $dateEvent = Carbon::parse($eventFree->start_date);
                $diffEv = $dateSearch->diffInDays($dateEvent);
               
                if($diffCal <= $diffEv){
                    return $dateCalendar;
                }else{
                    return $dateEvent;
                }
                
            }else if($turncalendar){
                return Carbon::parse($turncalendar->start_date);
            }else if($eventFree){
                return Carbon::parse($eventFree->start_date);
            }            
//            return [$turncalendar, $eventFree, $dateCalendar, $dateEvent, $startDateTurnCalendar, $startDateTurnEvent];
        }      
        return false;
    }

    static function turnsDayCalendar(int $microsite_id, string $date) {
        $now = Carbon::parse($date);
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        return res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("start_datetime")->get();
    }

    static function realDateByHousInDate(int $microsite_id, string $date, string $time) {

        $now = Carbon::parse($date);
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $lastTurn = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
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
                        ->orderBy("end_datetime", "DESC")->limit(1)->first();

        if ($lastTurn) {
            if (strcmp($lastTurn->end_date, $now->toDateString()) == 1) {
                if (strcmp($time, "00:00:00") >= 0 && strcmp($time, $lastTurn->hours_end) <= 0) {
                    return $lastTurn->end_date;
                }
            }
        }
        return $now->toDateString();
    }

   
    /**
     * Retorna fecha y hora real si existe hori en el calendario.
     * @param int $microsite_id
     * @param string $date
     * @param string $time
     * @return string | boolean
     */
    static function getDatetimeCalendar(int $microsite_id, string $date, string $time) {

        $now = Carbon::parse(trim($date) . " " . trim($time));
        $nextday = $now->copy()->addDay();
        
        $lastTurn = res_turn_calendar::fromMicrositeActives($microsite_id, $date, $date)->where(function($query) use ($time){
            $query = $query->whereRaw("(start_time < end_time AND '$time' >= start_time AND '$time' <= end_time)");
            $query = $query->orWhereRaw("(start_time > end_time AND '$time' >= start_time AND '$time' <= '23:59:59')");
            $query = $query->orWhereRaw("(start_time > end_time AND '$time' >= '00:00:00' AND '$time' <= end_time)");
            return $query;
        })->orderBy('start_date')->first();
        
        if($lastTurn){
            return (strcmp($lastTurn->start_time, $lastTurn->end_time) > 0 && strcmp($time, "00:00:00") >= 0 && strcmp($time, $lastTurn->end_time) <= 0)? $nextday->toDateTimeString():$now->toDateTimeString();
        }
        
        $eventFree = ev_event::eventFreeActive($date, $date)->select('*', DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"))
                ->where('ms_microsite_id', $microsite_id)->whereHas('turn', function($query) use ($time){
                    $query = $query->whereRaw("(hours_ini < hours_end AND '$time' >= hours_ini AND '$time' <= hours_end)");
                    $query = $query->orWhereRaw("(hours_ini > hours_end AND '$time' >= hours_ini AND '$time' <= '23:59:59')");
                    $query = $query->orWhereRaw("(hours_ini > hours_end AND '$time' >= '00:00:00' AND '$time' <= hours_end)");
                    return $query;
                })->with(['turn'])->orderBy('datetime_event')->first();
                
        if($eventFree){
            $lastTurn = $eventFree->turn;
            return (strcmp($lastTurn->hours_ini, $lastTurn->hours_end) > 0 && strcmp($time, "00:00:00") >= 0 && strcmp($time, $lastTurn->hours_end) <= 0)? $nextday->toDateTimeString():$now->toDateTimeString();
        }
        

        return false;
    }

//    static function existEvent(int $microsite_id, string $date, string $time) {
//
//        $now = Carbon::parse(trim($date) . " " . trim($time));
//        $nextday = $now->copy()->addDay();
//        $dayOfWeek = $now->dayOfWeek + 1;
//        $lastTurn = \App\Entities\ev_event::select(array(
//                            "res_turn.id",
//                            "ev_event.datetime_event",
//                            "res_turn.hours_ini",
//                            "res_turn.hours_end",
//                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',hours_ini) AS start_datetime"),
//                            DB::raw("'" . $now->toDateString() . "' AS start_date"),
//                            DB::raw("IF(hours_ini > hours_ini, '" . $now->toDateString() . "', '" . $nextday->toDateString() . "') AS end_date"),
//                            DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . "',' ',hours_end), CONCAT('" . $nextday->toDateString() . "',' ',hours_end)) AS end_datetime")
//                        ))
//                        ->join("res_turn", "res_turn.id", "=", "ev_event.res_turn_id")
//                        ->where("res_turn.ms_microsite_id", $microsite_id)
//                        ->where("ev_event.datetime_event", ">=", $now->toDateString(). " 00:00:00")
//                        ->where("ev_event.datetime_event", "<=", $nextday->toDateString(). " 05:00:00")
//                        ->orderBy("end_datetime")->get();
//        
//        return ($lastTurn->count() > 0);
//    }

    static function untilNextDay($hoursIni, $hoursEnd) {
        if (strcmp($hoursEnd, $hoursIni) == 1) {
            return true;
        }
        return false;
    }

    static function inRangeHours($time, $timeIni, $timeEnd) {
        if (strcmp($time, $timeIni) >= 0 && strcmp($time, $timeEnd) <= 0) {
            return true;
        }
        return false;
    }

    /**
     * Busca calcular los datos de tiempo para una reservacion
     * @param  int                  $microsite_id id del microsito a buscar
     * @return object               \App\res_reservation  | null
     */
    static function CalculeTimesReservationNow(int $microsite_id) {
        $start_date = self::realDate($microsite_id);
        $now = Carbon::now();
        $time = self::RoundBeforeTime($now->toTimeString());
        $now = Carbon::parse($start_date . " " . $time);
        return self::CalculeTimesReservation($microsite_id, $start_date, $time);
    }

    /**
     * Busca calcular los datos de tiempo para una reservacion 
     * @param  int                  $microsite_id id del microsito a buscar
     * @return object               \App\res_reservation  | null
     */
    static function CalculeTimesReservation(int $microsite_id, string $date, string $time) {

        $time = self::RoundBeforeTime($time);
        $now = Carbon::parse($date . " " . $time);
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        $lastTurn = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
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
                        ->where(DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time))"), ">=", $now->toDateTimeString())
                        ->orderBy("end_datetime")->get();

        $collect = [];

        if ($lastTurn->count() > 0) {

            $reservation = new \App\res_reservation();
            $date_reservation = $now->toDateString();
            $hours_reservation = $now->toTimeString();

            $diffHours = 999999;
            $encontroHorario = false;

            foreach ($lastTurn as $turn) {
                $diff = $diffHours;
                if (self::untilNextDay($turn->hours_end, $turn->hours_ini)) {
                    if (self::inRangeHours($now->toTimeString(), $turn->hours_ini, "23:59:59")) {
                        $date_reservation = $now->toDateString();
                        $hours_reservation = $now->toTimeString();
                        $encontroHorario = true;
                    } else if (self::inRangeHours($now->toTimeString(), "00:00:00", $turn->hours_end)) {
                        $date_reservation = $nextday->toDateString();
                        $hours_reservation = $nextday->toTimeString();
                        $encontroHorario = true;
                    } else if (strcmp($now->toTimeString(), $turn->hours_ini) < 0) {
                        $dateIni = $now->copy();
                        $dateEnd = Carbon::parse($now->toDateString() . " " . "23:59:59");
                        $diff = $dateEnd->diffInSeconds($dateIni);
                        $date_reservation = $now->toDateString();
                        $hours_reservation = $turn->hours_ini;
                    } else {
                        $dateIni = Carbon::parse($nextday->toDateString() . " " . $turn->hours_end);
                        $dateEnd = $nextday->copy();
                        $diff = $dateEnd->diffInSeconds($dateIni);
                        $date_reservation = $nextday->toDateString();
                        $hours_reservation = $turn->hours_end;
                    }
                } else {
                    if (self::inRangeHours($now->toTimeString(), $turn->hours_ini, $turn->hours_end)) {
                        $date_reservation = $now->toDateString();
                        $hours_reservation = $now->toTimeString();
                        $encontroHorario = true;
                    } else if (strcmp($now->toTimeString(), $turn->hours_ini) < 0) {
                        $dateIni = $now->copy();
                        $dateEnd = Carbon::parse($now->toDateString() . " " . $turn->hours_end);
                        $diff = $dateEnd->diffInSeconds($dateIni);
                        $date_reservation = $now->toDateString();
                        $hours_reservation = $turn->hours_ini;
                    } else {
                        $diff = strcmp($now->toTimeString(), $turn->hours_end);
                        $date_reservation = $now->toDateString();
                        $hours_reservation = $turn->hours_end;
                    }
                }
                //$collect[] = [$diff, $date_reservation, $hours_reservation];
                if ($diff <= $diffHours || $encontroHorario) {
                    $reservation->date_reservation = $date_reservation;
                    $reservation->hours_reservation = $hours_reservation;
                    $reservation->res_turn_id = $turn->id;
                    $diffHours = $diff;
                    if ($encontroHorario) {
                        $reservation->datetime_input = Carbon::parse($reservation->date_reservation . " " . $reservation->hours_reservation);
                        // $duration = TurnsHelper::sobremesa($reservation->res_turn_id, $guests);
                        break;
                    }
                }
            }
            return $reservation;
        }

        return false;
    }

    public static function RoundBeforeTime(String $time) {
        list($hour, $minute) = explode(":", $time);

        $residuo = fmod($minute, 15);
        $minute = $residuo == 0 ? $minute : $minute - $residuo;
        $minute = $minute == 0 ? '00' : $minute;

        return $hour . ':' . $minute . ':00';
    }

}
