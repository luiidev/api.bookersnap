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
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;

class res_turn_calendar extends Model {

    protected $table = "res_turn_calendar";
    public $timestamps = false;
    protected $fillable = ['res_type_turn_id', 'start_date', 'end_date', 'start_time', 'end_time', 'date_add', 'user_add', 'res_turn_id'];
    // protected $hidden = ['ms_microsite_id', 'ev_event_id', 'bs_user_id'];
    protected $casts = ["dayOfWeek" => "integer"];

    public function turn() {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function scopeFromMicrosite($query, int $microsite_id, string $start_date, string $end_date = null) {
        $query = $query->whereHas('turn', function($query) use($microsite_id) {
            $query->where('ms_microsite_id', $microsite_id);
        });
        if (is_null($end_date) || $start_date == $end_date) {
            $date = Carbon::parse($start_date);
            $query = $query->where("start_date", "<=", $start_date)
                    ->where("end_date", ">=", $start_date)
                    ->where(DB::raw("dayofweek(start_date)"), ($date->dayOfWeek + 1));
        } else {
            $query = $query->where(function($query) use($start_date, $end_date) {
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

    public function scopeFromMicrositeActives($query, int $microsite_id, string $start_date = null, string $end_date = null) {
        $now = Carbon::now();
        $yesterday = $now->copy()->yesterday();

        $condition = "dayofweek(start_date) >= " . ($now->dayOfWeek + 1);
        $optionA = "ADDDATE('" . $now->toDateString() . "', INTERVAL (dayofweek(start_date) - " . ($now->dayOfWeek + 1) . ") DAY)";
        $optionB = "ADDDATE('" . $now->toDateString() . "', INTERVAL (7 + dayofweek(start_date) - " . ($now->dayOfWeek + 1 ) . ") DAY)";
        $conditionFutures = "IF(start_date < '" . $now->toDateString() . "', IF($condition, $optionA , $optionB), start_date)";
        $conditionYesterday = "IF(CONCAT('" . $now->toDateString() . " ', res_turn_calendar.end_time) >= '" . $now->toDateTimeString() . "', '" . $now->toDateString() . "', '" . $yesterday->toDateString() . "')";
        $conditionActives = "IF(start_date <= '" . $yesterday->toDateString() . "' AND dayofweek(start_date) = " . ($yesterday->dayOfWeek + 1) . ", $conditionYesterday, $conditionFutures)";
        
        $replacestartdate = "IF(start_date <= '" . $yesterday->toDateString() . "' AND dayofweek(start_date) = " . ($yesterday->dayOfWeek + 1) . ", '".$yesterday->toDateString()."' , $conditionFutures)";

        $query = $query->select('*', DB::raw("$conditionActives AS start_date_real"))->whereHas('turn', function($query) use($microsite_id, $yesterday, $now) {
            
                    $query->where('ms_microsite_id', $microsite_id)->where(function($query) use ($yesterday, $now) {
                        return $query->whereRaw("res_turn.hours_ini > res_turn.hours_end")
                                        ->where(DB::raw("dayofweek(res_turn_calendar.start_date)"), ($yesterday->dayOfWeek + 1))
                                        ->where('res_turn_calendar.start_date', "<=", $yesterday->toDateString())
                                        ->where('res_turn_calendar.end_date', ">=", $yesterday->toDateString())
                                        ->where(DB::raw("CONCAT('" . $now->toDateString() . " ', res_turn.hours_end)"), ">=", $now->toDateTimeString())
                                        ->orWhere('res_turn_calendar.end_date', '>=', $now->toDateString());
                    });
                });
        
        $query = $query->where(DB::raw($conditionActives), ">=", $now->toDateTimeString());
        
        if (!is_null($start_date)) {
            $query = $query->whereRaw("$conditionActives >= ?", [$start_date]);
        }
        if (!is_null($end_date)) {
            $query = $query->whereRaw("$conditionActives <= ?", [$end_date]);
        }
        
        /* query select para test de datos */
//        $query = $query->select('res_turn_calendar.*', DB::raw("dayofweek(start_date) AS dayofweek"), DB::raw("($now->dayOfWeek + 1) AS dayofweekNow"), DB::raw("($yesterday->dayOfWeek + 1) AS dayofweekYesterady"), DB::raw("'" . $now->toDateTimeString() . "' AS DatetimeNow"), DB::raw("CONCAT('" . $yesterday->toDateString() . " ', res_turn_calendar.end_time) AS DateYesterady"), DB::raw("CONCAT('" . $now->toDateString() . " ', res_turn_calendar.end_time) AS DateNow"), DB::raw("(start_date <= '" . $yesterday->toDateString() . "') AS PAST"), DB::raw("dayofweek(start_date) <= " . ($yesterday->dayOfWeek + 1) . " AS RESULT"), DB::raw("$conditionActives AS start_date_active"));
        return $query;
    }

}
