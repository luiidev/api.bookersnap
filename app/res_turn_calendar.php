<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_reservation
 *
 * @author USER
 */
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Model;

class res_turn_calendar extends Model
{

    protected $table    = "res_turn_calendar";
    public $timestamps  = false;
    protected $fillable = ['res_type_turn_id', 'start_date', 'end_date', 'start_time', 'end_time', 'date_add', 'user_add', 'res_turn_id'];
    // protected $hidden = ['ms_microsite_id', 'ev_event_id', 'bs_user_id'];
    protected $casts = ["dayOfWeek" => "integer"];

    public function turn()
    {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function scopeFromMicrosite($query, int $microsite_id, string $start_date, string $end_date = null)
    {
        $query = $query->whereHas('turn', function ($query) use ($microsite_id) {
            $query->where('ms_microsite_id', $microsite_id);
        });
        if (is_null($end_date) || $start_date == $end_date) {
            $date  = Carbon::parse($start_date);
            $query = $query->where("start_date", "<=", $start_date)
                ->where("end_date", ">=", $start_date)
                ->where(DB::raw("dayofweek(start_date)"), ($date->dayOfWeek + 1));
        } else {
            $query = $query->where(function ($query) use ($start_date, $end_date) {
                return $query->where("start_date", "<=", $start_date)->where("end_date", ">=", $end_date)
                    ->orWhere("start_date", "<=", $end_date)->where("start_date", ">=", $end_date)
                    ->orWhere("start_date", ">=", $start_date)->where("start_date", "<=", $end_date);
            });
        }
        return $query;
    }

    /**
     * Horaios programados en el calendario para fechas mayores a la fecha actual puede tambien ser filtrado por un rango de fechas $start_date y $end_date
     * @param Model $query
     * @param int $microsite_id
     * @param string $start_date
     * @param string $end_date
     * @return Model
     */
    public function scopeFromMicrositeActives($query, int $microsite_id, string $start_date = null, string $end_date = null)
    {

        $datenow = Carbon::now();
        $istoday = false;
        if (strcmp($start_date, $datenow->toDateString()) == 1) {
            $now = Carbon::parse($start_date);
        } else {
            $now        = $datenow->copy();
            $start_date = $datenow->toDateString();
            $istoday    = true;
        }
        $yesterday = $now->copy()->subDay();
        $nextday   = $now->copy()->addDay();

        $query = $query->whereHas('turn', function ($query) use ($microsite_id) {
            $query = $query->where('ms_microsite_id', $microsite_id);
            return $query;
        });

        $query = $query->where(function ($query) use ($yesterday, $now, $nextday, $istoday, $start_date, $end_date) {

            if ($istoday) {
                /* Horarios del dia de ayer que esta activos hoy */
                $query = $query->whereRaw("start_time > end_time")
                    ->whereRaw("dayofweek(start_date) = ?", [($yesterday->dayOfWeek + 1)])
                    ->where('start_date', "<=", $yesterday->toDateString())
                    ->where('end_date', ">=", $yesterday->toDateString())
                    ->whereRaw("CONCAT(? , end_time) >= ?", [$now->toDateString(), $now->toDateTimeString()]);

                /* Horarios activos hoy */
                $query = $query->orWhereRaw("dayofweek(start_date) = ?", [($now->dayOfWeek + 1)])
                    ->where('start_date', "<=", $now->toDateString())
                    ->where('end_date', ">=", $now->toDateString())
                    ->whereRaw("IF(start_time > end_time, CONCAT(?, ' ' ,end_time), CONCAT(?, ' ' ,end_time)) >= ?", [$nextday->toDateString(), $now->toDateString(), $now->toDateTimeString()]);

                /* Horarios activos el dia siguiente */
                $query = $query->orWhere('end_date', '>', $nextday->toDateString());
            } else {

                /* Horarios activos a partir de una fecha */
                $query = $query->orWhere('end_date', '>=', $now->toDateString());
            }

            return $query;
        });

        $datetimeEnd = "IF(start_time > end_time, ADDDATE(CONCAT(end_date, ' ',end_time), INTERVAL 1 DAY), CONCAT(end_date, ' ',end_time))";

        $condition        = "dayofweek(start_date) >= " . ($now->dayOfWeek + 1);
        $optionA          = "ADDDATE('" . $now->toDateString() . "', INTERVAL (dayofweek(start_date) - " . ($now->dayOfWeek + 1) . ") DAY)";
        $optionB          = "ADDDATE('" . $now->toDateString() . "', INTERVAL (7 + dayofweek(start_date) - " . ($now->dayOfWeek + 1) . ") DAY)";
        $conditionFutures = "IF(start_date < '" . $now->toDateString() . "', IF($condition, $optionA , $optionB), start_date)";

        $conditionYesterday = "IF((start_time >= end_time) AND CONCAT('" . $now->toDateString() . " ', end_time) >= '" . $now->toDateTimeString() . "', '" . $yesterday->toDateString() . "', '" . $yesterday->copy()->subDay()->toDateString() . "')";
        $startDateActive    = "IF(start_date <= '" . $yesterday->toDateString() . "' AND dayofweek(start_date) = " . ($yesterday->dayOfWeek + 1) . ", $conditionYesterday, $conditionFutures)";

        $condition         = "dayofweek(start_date) >= " . ($yesterday->dayOfWeek + 1);
        $optionA           = "ADDDATE('" . $yesterday->toDateString() . "', INTERVAL (dayofweek(start_date) - " . ($yesterday->dayOfWeek + 1) . ") DAY)";
        $optionB           = "ADDDATE('" . $yesterday->toDateString() . "', INTERVAL (7 + dayofweek(start_date) - " . ($yesterday->dayOfWeek + 1) . ") DAY)";
        $realdateyesterday = "IF(start_date < '" . $yesterday->toDateString() . "', IF($condition, $optionA , $optionB), start_date)";

//        $startDateActive = "IF(start_date <= '" . $yesterday->toDateString() . "' AND dayofweek(start_date) = " . ($yesterday->dayOfWeek + 1) . ", $conditionYesterday, $conditionFutures)";

        if (!is_null($start_date)) {
            if (strcmp($datenow->toDateString(), $start_date) == 0) {
                $condYesterday = "IF((start_time > end_time) AND CONCAT('" . $now->toDateString() . " ', end_time) >= '" . $now->toDateTimeString() . "',  '" . $now->toDateString() . "', '" . $yesterday->toDateString() . "')";
                $startActive   = "IF(start_date <= '" . $yesterday->toDateString() . "' AND dayofweek(start_date) = " . ($yesterday->dayOfWeek + 1) . ", $condYesterday, $conditionFutures)";
                $query         = $query->whereRaw("CONCAT($startActive, ' ', end_time) >= ?", [$datenow->toDateTimeString()]);
            } else {
                $query = $query->whereRaw("$startDateActive >= ?", [$start_date]);
            }
        }
        if (!is_null($end_date)) {
            $query = $query->whereRaw("$startDateActive <= ?", [$end_date]);
        }
        /* query select para test de datos */
        $query = $query->select('*', DB::raw("$startDateActive AS start_date"));
//        $query = $query->select('res_turn_calendar.*',
        //                DB::raw("'" . $now->toDateString() . "' AS HOY"),
        //                DB::raw("'" . $yesterday->toDateString() . "' AS AYER"),
        //                DB::raw("'" . $datenow->toDateString() . "' AS DATENOW"),
        //                DB::raw("CONCAT($startActive, ' ', end_time) AS CONAT_YESTERDAY"),
        //                DB::raw("$startActive AS left_yesterday_result"),
        //                DB::raw("IF((start_time > end_time) AND CONCAT('" . $now->toDateString() . " ', end_time) >= '" . $now->toDateTimeString() . "',  '" . $now->toDateString() . "', '" . $yesterday->toDateString() . "') AS start_date_active_lll"),
        //                DB::raw("IF(start_date <= '" . $yesterday->toDateString() . "', 'start_date <= " . $yesterday->toDateString() . "', 'start_date > " . $yesterday->toDateString() . "') AS left_yesterday"),
        //                DB::raw("$condYesterday AS condition_yesterday"),
        //                DB::raw("dayofweek(start_date) AS dayofweek"),
        //                DB::raw("($now->dayOfWeek + 1) AS dayofweekNow"),
        //                DB::raw("($yesterday->dayOfWeek + 1) AS dayofweekYesterady"),
        //                DB::raw("'" . $now->toDateTimeString() . "' AS DatetimeNow"),
        //                DB::raw("CONCAT('" . $yesterday->toDateString() . " ', res_turn_calendar.end_time) AS DateYesterady"),
        //                DB::raw("CONCAT('" . $now->toDateString() . " ', res_turn_calendar.end_time) AS DateNow"),
        //                DB::raw("(start_date <= '" . $yesterday->toDateString() . "') AS PAST"),
        //                DB::raw("dayofweek(start_date) <= " . ($yesterday->dayOfWeek + 1) . " AS RESULT"),
        //                DB::raw("$startDateActive AS start_date_active"));
        return $query;
    }

}
