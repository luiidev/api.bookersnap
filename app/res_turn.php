<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_turn_zone
 *
 * @author USER
 */
use App\res_turn_calendar;
use DB;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class res_turn extends Model {

    protected $table = "res_turn";
    public $timestamps = false;
    protected $fillable = [
        'id',
        'on_table',
        'hours_ini',
        'hours_end',
        'status',
        'date_add',
        'date_upd',
        'user_add',
        'user_upd',
        'early',
        // 'res_zone_id',
        'ms_microsite_id',
        'res_type_turn_id',
    ];
    protected $hidden = [
        'date_add',
        'date_upd',
        'user_add',
        'user_upd',
//        'ms_microsite_id',
    ];

    /* public function days() {
      return $this->hasMany('App\res_day_turn_zone', 'res_turn_id');
      } */

    public function zones() {
        return $this->belongsToMany('App\res_zone', 'res_turn_zone', 'res_turn_id', 'res_zone_id');
    }

    public function typeTurn() {
        return $this->belongsTo('App\res_type_turn', 'res_type_turn_id');
    }

    public function turnZone() {
        return $this->hasMany('App\res_turn_zone', 'res_turn_id');
        //return $this->belongsToMany('App\res_turn_zone', 'res_turn_id');
    }

    public function turnTable() {
        return $this->hasMany('App\res_turn_table', 'res_turn_id');
    }

    public function turnTime() {
        return $this->hasMany('App\res_turn_time', 'res_turn_id');
    }

    public function availability() {
        return $this->hasMany('App\res_turn_zone', 'res_turn_id');
    }

    public function weekDays() {
        return $this->hasMany(res_turn_calendar::class)
                        ->select("res_turn_id", DB::raw("dayofweek(start_date) as day"))
                        ->where("end_date", "9999-12-31")
                        ->groupBy("day");
    }

    public function calendar() {
//        return $this->hasMany('App\res_turn_calendar', 'res_turn_id')->where("end_date", ">=", date('Y-m-d'));
        return $this->hasMany('App\res_turn_calendar', 'res_turn_id');
    }

    public function turnCalendar() {
        return $this->hasMany('App\res_turn_calendar', 'res_turn_id');
    }

    public function events() {
        return $this->hasMany('App\Entities\ev_event', 'res_turn_id');
    }

    public function getWeekDaysAttribute() {
        $this->addHidden(["weekDays"]);
        return $this->relations["weekDays"]->pluck("day");
    }

//    public function delete() {
    //        $this->days()->delete();
    //        return parent::delete();
    //    }

    /**
     * Turnos configurados en el calendario en una fecha o rango de fecha
     * @param object $query
     * @param string $start_date
     * @param string $end_date
     * @return \App\Entities\ev_event
     */
    public function scopeInCalendar($query, string $start_date, string $end_date = null) {

        return $query->whereHas('calendar', function($query) use ($start_date, $end_date) {
                    if (is_null($end_date)) {
                        $date = Carbon::parse($start_date);
                        return $query->where("start_date", "<=", $start_date)->where("end_date", ">=", $start_date)->where(DB::raw("dayofweek(start_date)"), ($date->dayOfWeek + 1));
                    } else {
                        return $query->where("start_date", "<=", $start_date)->where("end_date", ">=", $start_date)
                                        ->orWhere("start_date", "<=", $end_date)->where("end_date", ">=", $end_date);
                    }
                });
    }

    public function scopeInEventFree($query, string $start_date, string $end_date = null) {
        $end_date = is_null($end_date) ? $start_date : $end_date;
        return $query->whereHas('events', function($query) use ($start_date, $end_date) {
                    return $query->where('bs_type_event_id', 1)->whereBetween(DB::raw("DATE_FORMAT(datetime_event, '%Y-%m-%d')"), [$start_date, $end_date]);
                });
    }

    /**
     * Turno activos por eventos en un rango de fechas 
     * @param type $query
     * @param string $start_date
     * @param string $end_date
     * @return type
     */
    public function scopeInEventFreeActive($query, string $start_date, string $end_date = null) {
        $now = Carbon::now();
        $yesterday = $now->copy()->yesterday();
        $query = $query->whereHas('events', function($query) use ($start_date, $end_date, $now, $yesterday) {            
            $dateformat = "DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')";
            $dateYesterday = "IF(hours_ini > hours_end, '" . $now->toDateString() . "', '" . $yesterday->toDateString() . "')";
            $datetimeYesterday = "IF(hours_ini > hours_end, CONCAT('" . $now->toDateString() . " ', hours_end), CONCAT('" . $yesterday->toDateString() . " ', hours_end))";
            $conditionB = "IF($dateYesterday = '" . $now->toDateString() . "' AND $datetimeYesterday >= '" . $now->toDateTimeString() . "', '" . $yesterday->toDateString() . "', '" . $now->toDateString() . "')";
            $conditionActives = "IF($dateformat > '" . $yesterday->toDateString() . "', $dateformat, $conditionB)";
        
            $query = $query->where('bs_type_event_id', 1)->where('status', 1)->whereRaw("$conditionActives >= ?", [$yesterday->toDateString()]);
            if (!is_null($start_date)) {
                $query = $query->whereRaw("$conditionActives >= ?", [$start_date]);
            }
            if (!is_null($end_date)) {
                $query = $query->whereRaw("$conditionActives <= ?", [$end_date]);
            }            
            return $query;
        });        
        return $query;
    }

    public function scopeInTime($query, string $time) {
        $now = Carbon::parse("2016-01-01 " . $time);
        $nextday = $now->copy()->addDay();
        return $query->where(function($query) use ($now, $nextday) {
                    return $query->where(DB::raw("CONCAT('" . $now->toDateString() . " ', hours_ini)"), "<=", $now->toDateTimeString())
                                    ->where(DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . " ', hours_end), CONCAT('" . $nextday->toDateString() . " ', hours_end))"), ">=", $now->toDateTimeString())
                                    ->orwhere(DB::raw("CONCAT('" . $now->toDateString() . " ', hours_ini)"), "<=", $nextday->toDateTimeString())
                                    ->where(DB::raw("IF(hours_end > hours_ini, CONCAT('" . $now->toDateString() . " ', hours_end), CONCAT('" . $nextday->toDateString() . " ', hours_end))"), ">=", $nextday->toDateTimeString());
                });
    }

}
