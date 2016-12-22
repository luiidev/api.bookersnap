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
    static function searchDate(int $microsite_id, $date) {
        
        $datenow = Carbon::now();
        
        $turncalendar = (strcmp($datenow->toDateString(), $date) == 1)?res_turn_calendar::fromMicrositeActives($microsite_id, $date, $date)->orderBy('start_date')->first():null;        
        if(!$turncalendar){
            $turncalendar = res_turn_calendar::fromMicrositeActives($microsite_id)->orderBy('start_date')->first();
        }
        $startDateTurnCalendar = ($turncalendar)?$turncalendar->start_date: null;
        
        
        $eventFree = (strcmp($datenow->toDateString(), $date) == 1) ? ev_event::eventFreeActive($date, $date)->select('id','name',DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"))->orderBy('datetime_event')->first():null;
        if(!$eventFree){
            $eventFree = ev_event::eventFreeActive()->select('id','name',DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d') AS start_date"))->orderBy('datetime_event')->first();
        }
        
        $startDateTurnEvent = ($eventFree)?$eventFree->start_date: null;
        
        $dateFinal = null;
        if(!is_null($startDateTurnCalendar) && !is_null($startDateTurnEvent)){
            $dateFinal = (strcmp($startDateTurnCalendar, $startDateTurnEvent) != 1) ? $startDateTurnCalendar:$startDateTurnEvent;
        }else if(!is_null($startDateTurnCalendar)){
            $dateFinal =  $startDateTurnCalendar;
        }else if(!is_null($startDateTurnEvent)){
            $dateFinal =  $startDateTurnEvent;
        }        
//        return [$turncalendar, $eventFree];
        if(!is_null($dateFinal)){
            return Carbon::parse($dateFinal);
        }
        return $dateFinal;
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

//        $lastTurn = TurnsHelper::JoinCalendarByDate($microsite_id, $date);

        $now = Carbon::parse(trim($date) . " " . trim($time));
        $nextday = $now->copy()->addDay();

        $lastTurn = res_turn::inCalendar($date)->inTime($time)->where('ms_microsite_id', $microsite_id)->first();

        if ($lastTurn) {
            $comp = strcmp($lastTurn->hours_end, $lastTurn->hours_ini);
            if ($comp == 1) {
                return $now->toDateTimeString();
            } else {
                $datetime_ini = $now->toDateString() . " " . $lastTurn->hours_ini;
                $datetime_end = $now->toDateString() . " 23:59:59";
                $compareIni = (strcmp($datetime_ini, $now->toDateTimeString()) != 1);
                $compareEnd = (strcmp($datetime_end, $now->toDateTimeString()) != -1);
                if ($compareIni && $compareEnd) {
                    return $now->toDateTimeString();
                }

                $datetime_ini = $nextday->toDateString() . " 00:00:00";
                $datetime_end = $nextday->toDateString() . " " . $lastTurn->hours_end;
                $compareIni = (strcmp($datetime_ini, $nextday->toDateTimeString()) != 1);
                $compareEnd = (strcmp($datetime_end, $nextday->toDateTimeString()) != -1);
                if ($compareIni && $compareEnd) {
                    return $nextday->toDateTimeString();
                }
            }
        }


        $lastTurn = res_turn::inEventFree($date)->inTime($time)->where('ms_microsite_id', $microsite_id)->first();

        if ($lastTurn) {
            $comp = strcmp($lastTurn->hours_end, $lastTurn->hours_ini);
            if ($comp == 1) {
                return $now->toDateTimeString();
            } else {
                $datetime_ini = $now->toDateString() . " " . $lastTurn->hours_ini;
                $datetime_end = $now->toDateString() . " 23:59:59";
                $compareIni = (strcmp($datetime_ini, $now->toDateTimeString()) != 1);
                $compareEnd = (strcmp($datetime_end, $now->toDateTimeString()) != -1);
                if ($compareIni && $compareEnd) {
                    return $now->toDateTimeString();
                }

                $datetime_ini = $nextday->toDateString() . " 00:00:00";
                $datetime_end = $nextday->toDateString() . " " . $lastTurn->hours_end;
                $compareIni = (strcmp($datetime_ini, $nextday->toDateTimeString()) != 1);
                $compareEnd = (strcmp($datetime_end, $nextday->toDateTimeString()) != -1);
                if ($compareIni && $compareEnd) {
                    return $nextday->toDateTimeString();
                }
            }
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
