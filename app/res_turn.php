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
use Illuminate\Database\Eloquent\Model;

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
        'res_zone_id',
        'ms_microsite_id',
        'res_type_turn_id'
    ];
    
    protected $hidden = [
        'date_add',
        'date_upd',
        'user_add',
        'user_upd',
        'res_zone_id',
        'ms_microsite_id',
        'res_type_turn_id'
    ];
    

//    public function days() {
//        return $this->hasMany('App\res_day_turn_zone', 'res_turn_zone_id');
//    }

//    public function type() {
//        return $this->belongsTo('App\res_type_turn', 'res_type_turn_id');
//    }
    
//    public function zone() {
//        return $this->belongsTo('App\res_zone', 'res_zone_id');
//    }
    
//    public function delete() {
//        $this->days()->delete();
//        return parent::delete();
//    }

}