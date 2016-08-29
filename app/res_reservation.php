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

class res_reservation extends Model {

    protected $table = "res_reservation";
    public $timestamps = false;
//    protected $fillable = ['name', 'sketch', 'status', 'type_zone', 'join_table', 'status_smoker', 'people_standing', 'user_add', 'user_upd', 'ev_event_id', 'ms_microsite_id'];
//    protected $hidden = ['ms_microsite_id', 'ev_event_id', 'bs_user_id'];
    
    public function status() {
        return $this->belongsTo('App\res_reservation_status', 'res_reservation_status_id');
    }
    
    public function tables() {
        return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id');
    }
    
    
}
