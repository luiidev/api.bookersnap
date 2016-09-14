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
use Illuminate\Database\Eloquent\Model;
use DB;

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
        'res_type_turn_id'
    ];
    
    protected $hidden = [
        'date_add',
        'date_upd',
        'user_add',
        'user_upd',
        'ms_microsite_id',
    ];
    

    /*public function days() {
       return $this->hasMany('App\res_day_turn_zone', 'res_turn_id');
    }*/

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
    
    public function availability() {
        return $this->hasMany('App\res_turn_zone', 'res_turn_id');
    }

    public function weekDays()
    {
        return $this->hasMany(res_turn_calendar::class)
                                ->select( "res_turn_id", DB::raw("dayofweek(start_date) as day") )
                                ->where("end_date",  "9999-12-31")
                                ->groupBy("day");
    }

    public function getWeekDaysAttribute()
    {
        $this->addHidden(["weekDays"]);
        return $this->relations["weekDays"]->pluck("day");
    }
    
//    public function delete() {
//        $this->days()->delete();
//        return parent::delete();
//    }

}
