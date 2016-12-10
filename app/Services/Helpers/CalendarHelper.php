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
use Illuminate\Support\Facades\DB;

class CalendarHelper {
    
    /**
     * Busca disponibilidad de una mesa en un hora determinada
     * @param  int                  $microsite_id id del microsito a buscarreturn "test"
     * @param  string $date        fecha de la reservacion
     * @return array               ['datetimeOpen' => DATETIME, 'datetimeClose' => DATETIME]
     */
    static function realDateTimeOpenAndClose(int $microsite_id, string $date = null) {
        
        $now = is_null($date) ? CalendarHelper::realDate($microsite_id): Carbon::parse($date);
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
        $datetimeOpen = $now->toDateString(). " 00:00:00";
        $datetimeClose = $datetimeOpen . " 23:59:59";
        if($count > 0){
            $datetimeOpen = $firstTurn->start_datetime;
            $datetimeClose = $firstTurn->end_datetime;
            if($count > 1){
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
                        ->orderBy("start_datetime", 'DESC')->limit(1)->first();

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
                            ->orderBy("end_datetime", 'DESC')->limit(1)->first();

            if ($turnYesterday) {
                $endDatetime = Carbon::parse($turnYesterday->end_datetime);
                $inDate = $endDatetime->toDateString() <=> $now->toDateString();
                $inDateTime = $endDatetime->toDateTimeString() <=> $now->toDateTimeString();
                if ($inDate == 0 && $inDateTime != -1) {
                    return $yesterday->toDateString();
                }
            }
            return $now->toDateString();
        }
        return $date;
    }

    /**
     * Busca la fecha de apertura mas proxima
     * @param  int    $microsite_id     id del microsito a buscar
     * @param  string $date             fecha de inicio de busqueda
     * @return array                    Carbon
     */
    static function searchDate(int $microsite_id, $date) {

        $dateIn = Carbon::parse($date);
        $now = Carbon::now();
        $esUnaFechaMayor = (($dateIn->toDateString() <=> $now->toDateString()) === 1);
        if ($esUnaFechaMayor) {
            $now = $dateIn;
        }

        $yesterday = $now->copy()->yesterday();
        $dayOfWeek = $yesterday->dayOfWeek + 1;

        $turns = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            DB::raw("CONCAT('" . $yesterday->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $yesterday->toDateString())
                        ->where("end_date", ">=", $yesterday->toDateString())
                        ->orderBy("end_datetime", 'DESC')->limit(1)->first();

        if ($turns) {
            $endDatetime = Carbon::parse($turns->end_datetime);
            $inDate = $endDatetime->toDateString() <=> $now->toDateString();
            $inDateTime = $endDatetime->toDateTimeString() <=> $now->toDateTimeString();
            if ($inDate == 0 && $inDateTime != -1) {
                return $now->copy()->yesterday();
            }
        }


        $date = $now->toDateString();
        $dayofweek = $now->dayOfWeek + 1;

        $turns = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            DB::raw("dayofweek(start_date) AS dayofweek"),
                            DB::raw("IF(start_date < '$date', "
                                    . "ADDDATE('$date', INTERVAL IF(dayofweek(start_date) >= $dayofweek, (dayofweek(start_date) - $dayofweek), 7 + (dayofweek(start_date)-$dayofweek)) DAY), "
                                    . "start_date) AS date_ini"),
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            "start_date",
                            "end_date"
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where(function($query) use ($now) {
                            return $query->where("start_date", "<=", $now->toDateString())
                                    ->where("end_date", ">=", $now->toDateString())
                                    ->orWhere("start_date", ">=", $now->toDateString());
                        })->orderBy("date_ini")->limit(1)->first();

        if ($turns) {
            return Carbon::parse($turns->date_ini . " 00:00:00");
        }

        return $now;
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
        
        return $now = Carbon::parse($date);
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
        
        if($lastTurn){
            if(strcmp($lastTurn->end_date, $now->toDateString()) == 1){
                if(strcmp($time, "00:00:00") >= 0 && strcmp($time, $lastTurn->hours_end) <= 0){
                    return $lastTurn->end_date;
                }
            }
        }
        
        $now->toDateString();
    }

}
