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
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('state', function (Builder $builder) {
            $builder->where('status', '<>', 2);
        });
    }
    
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

    /*
     * **************************************************************************************************************************************************************************
     * ####################################################################################################################################################################### **
     * ####################################################################################################################################################################### **
     * ##########               ############################################################################################################################################################# **
     * ##########    ############################################################################################################################################################# **
     * ##########    ############################################################################################################################################################# **
     * ##########    ############################################################################################################################################################# **
     * ##########               ############################################################################################################################################################# **
     * #####################    ################################################################################################################################################## **
     * #####################    ################################################################################################################################################## **
     * #####################    ################################################################################################################################################## **
     * ##########               ############################################################################################################################################################# **
     * ####################################################################################################################################################################### **
     * ####################################################################################################################################################################### **
     * **************************************************************************************************************************************************************************
     */

    /**
     * Lista de turnos que se usan para reservar ordenados por horario
     * @param type $query
     * @param int $microsite_id
     * @param string $date
     * @param string $time
     * @return type
     */
    public function scopeTurnReservationOld($query, int $microsite_id, string $date, string $time = null, string $sort = "ASC") {
        
        $datetime = is_null($time)? $date: ($date . " " . $time);
        $datenow = Carbon::parse($datetime);
        $nextday = $datenow->copy()->addDay();
        
        $timenow = $datenow->toTimeString();
        $datestringNow = $datenow->toDateString();
        $datetimestringNow = $datenow->toDateTimeString();
        $datestringNextday = $nextday->toDateString();
        $datetimestringNextday = $nextday->toDateTimeString();  
        
        $datetimeopen = "CONCAT('$datestringNow ', hours_ini)";
        $datetimecloseNow = "CONCAT('$datestringNow ', hours_end)";
        $datetimecloseNextday = "CONCAT('$datestringNextday ', hours_end)";
        $datetiomeclose = "IF(hours_ini < hours_end, $datetimecloseNow, $datetimecloseNextday)";     
        
        $datetimenow = "IF(hours_ini < hours_end, '$datetimestringNow', '$datetimestringNextday')";
//        $datetimenow = "IF(hours_ini > hours_end ,'$datetimestringNextday', '$datetimestringNow')";
        
//        $conditionA = "(hours_ini < hours_end AND '00:00:00' < '$timenow' AND hours_end > '$timenow')";
//        $conditionB = "(hours_ini < hours_end AND '00:00:00' < '$timenow' AND hours_end > '$timenow')";
        
        $query = $query->where(function($query) use($datenow) {
            $query = $query->where(function($query) use($datenow) {
                $query = $query->InCalendar($datenow->toDateString());
                return $query;
            });
            $query = $query->orwhere(function($query) use($datenow) {
                $query = $query->InEventFree($datenow->toDateString(), $datenow->toDateString());
                return $query;
            });
            return $query;
        });
        if(!is_null($time)){
            $query = $query->whereRaw("$datetiomeclose >= $datetimenow");
//            $query = $query->whereRaw("$datetiomeclose >= ?", [$datenow->toDateTimeString()]);
        }
        $sort = strtoupper($sort);
        $sort = ($sort == "DESC")?$sort:"ASC";
        $query = $query->where("ms_microsite_id", $microsite_id)->select("*", DB::raw("$datetimenow AS __DATETIME_NOW"),DB::raw("$datetimeopen AS __DATETIME_OPEN"), DB::raw("$datetiomeclose AS __DATETIME_CLOSE"))->orderBy("__DATETIME_CLOSE", $sort);
        return $query;
    }
    
    /**
     * Lista de turnos que se usan para reservar en una fecha ordenados por horario
     * @param type $query
     * @param int $microsite_id
     * @param string $date
     * @param string $time
     * @return type
     */
    public static function scopeTurnReservation($query, int $microsite_id, string $date, string $sort = "ASC") {
        
        $datenow = Carbon::parse($date);
        $nextday = $datenow->copy()->addDay();
        
        $timenow = $datenow->toTimeString();
        $datestringNow = $datenow->toDateString();
        $datetimestringNow = $datenow->toDateTimeString();
        $datestringNextday = $nextday->toDateString();
        $datetimestringNextday = $nextday->toDateTimeString();  
        
        $datetimeopen = "CONCAT('$datestringNow ', hours_ini)";
        $datetimecloseNow = "CONCAT('$datestringNow ', hours_end)";
        $datetimecloseNextday = "CONCAT('$datestringNextday ', hours_end)";
        $datetiomeclose = "IF(hours_ini < hours_end, $datetimecloseNow, $datetimecloseNextday)";        
        $datetimenow = "IF(hours_ini < hours_end, '$datetimestringNow', '$datetimestringNextday')";
        
        $query = $query->where(function($query) use($datenow) {
            $query = $query->where(function($query) use($datenow) {
                $query = $query->InCalendar($datenow->toDateString());
                return $query;
            });
            $query = $query->orwhere(function($query) use($datenow) {
                $query = $query->InEventFree($datenow->toDateString(), $datenow->toDateString());
                return $query;
            });
            return $query;
        });
        
        $sort = strtoupper($sort);
        $sort = ($sort == "DESC")?$sort:"ASC";
        $query = $query->where("ms_microsite_id", $microsite_id)->select("*", DB::raw("$datetimenow AS __DATETIME_NOW"),DB::raw("$datetimeopen AS __DATETIME_OPEN"), DB::raw("$datetiomeclose AS __DATETIME_CLOSE"))->orderBy("__DATETIME_CLOSE", $sort);
        return $query;
    }

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

    public function scopeInEventFree($query, string $start_date = null, string $end_date = null) {

        return $query->whereHas('events', function($query) use ($start_date, $end_date) {
                    $query = $query->where('bs_type_event_id', 1);
                    if (!is_null($start_date)) {
                        $query = $query->where(DB::raw("DATE_FORMAT(datetime_event, '%Y-%m-%d')"), ">=", [$start_date]);
                    }
                    if (!is_null($end_date)) {
                        $query = $query->where(DB::raw("DATE_FORMAT(datetime_event, '%Y-%m-%d')"), "<=", [$end_date]);
                    }
                    return $query;
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
