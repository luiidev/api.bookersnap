<?php

namespace App\Services\Helpers;

/**
 * Helpers para manejo de reservaciones
 */
use App\res_turn_calendar;
use Carbon\Carbon;
use DB;

class ReservationHelper {

    const _ID_SOURCE_RESERVATION_HOSTESS = 1;
    const _ID_SOURCE_RESERVATION_WEB = 4;
    const _ID_STATUS_RESERVATION_RESERVED = 1;
    const _ID_STATUS_RESERVATION_SEATED = 4;
    const _ID_STATUS_RESERVATION_RELEASED = 5;
    const _ID_STATUS_RESERVATION_CANCELED = 6;
    const _ID_STATUS_RESERVATION_ABSENT = 7;

    /**
     * Inicializar datos de reservación
     * @param int $microsite_id     ID del micrositio
     * @param string $date          Fecha de la reservacion
     * @param string $time          Hora de la reservación
     * @param string $duration      Número de invitados
     * @param int $guests           Número de invitados
     * @param int $status_id        ID des estado de la reservación
     * @return boolean|\App\res_reservation  
     */
    public static function init(int $microsite_id, string $date, string $time, string $duration = null, int $guests, int $status_id = self::_ID_STATUS_RESERVATION_RESERVED) : \App\res_reservation {

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
                
                if ($diff <= $diffHours || $encontroHorario) {
                    $reservation->date_reservation = $date_reservation;
                    $reservation->hours_reservation = $hours_reservation;
                    $reservation->res_turn_id = $turn->id;
                    $diffHours = $diff;
                    if ($encontroHorario) {                   
                        $duration = isset($duration) ? $duration : self::sobremesa($reservation->res_turn_id, $guests);                        
                        $datetimeInput = Carbon::parse($reservation->date_reservation . " " . $reservation->hours_reservation);                        
                        $datetimeOutput = self::addTime($datetimeInput, $duration);
                        $reservation->hours_duration = $duration;
                        $reservation->datetime_input = $datetimeInput->toDateTimeString();
                        $reservation->datetime_output = $datetimeOutput->toDateTimeString();
                        $reservation->res_reservation_status_id = $status_id;
                        break;
                    }
                }
            }
            return $reservation;
        }

        return false;
    }
    
    private static function redefine(\App\res_reservation &$reservation, $hours, $time, $guests) {
        
    }

    private static function untilNextDay($hoursIni, $hoursEnd) {
        if (strcmp($hoursEnd, $hoursIni) == 1) {
            return true;
        }
        return false;
    }
    
    private static function inRangeHours($time, $timeIni, $timeEnd) {
        if (strcmp($time, $timeIni) >= 0 && strcmp($time, $timeEnd) <= 0) {
            return true;
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
    
    private static function DefineTimeNewReservation(\App\res_reservation &$reservation, $statusId) {
        
        $datetimeInput = Carbon::parse($reservation->date_reservation . " " . $reservation->hours_reservation);
        if ($statusId < self::_ID_STATUS_RESERVATION_SEATED) {
            $reservation->datetime_input  = trim($reservation->date_reservation) . ' ' . trim($reservation->hours_reservation);
            $reservation->datetime_output = self::addTime($reservation->datetime_input, $reservation->hours_duration);
        } else if ($statusId == self::_ID_STATUS_RESERVATION_SEATED) {
            $reservation->datetime_input  = $now->toDateTimeString();
            $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);
        } else if ($statusId == self::_ID_STATUS_RESERVATION_RELEASED || $statusId == self::_ID_STATUS_RESERVATION_CANCELED || $statusId == self::_ID_STATUS_RESERVATION_ABSENT) {            
            $reservation->datetime_input  = ($action == "create") ? $now->toDateTimeString():$reservation->datetime_input;
            $reservation->datetime_output = $now->toDateTimeString();
        }
    }
    
    private static function DefineTimeUpdateReservation($date, $time, $status) {
        
    }
    
    private static function sobremesa(int $tunr_id, int $guests) {
        return TurnsHelper::sobremesa($tunr_id, $guests);
    }
    
    /**
     * 
     * @param Carbon $datetime
     * @param type $time
     * @return Carbon
     */
    private static function addTime(Carbon $datetime, $time) : Carbon{
        list($hours, $minute) = explode(":", $time);
        return  $datetime->copy()->addHours($hours)->addMinutes($minute);
    }

}
