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

class res_turn_calendar extends Model {

    protected $table = "res_turn_calendar";
    public $timestamps = false;
   protected $fillable = ['res_type_turn_id', 'start_date', 'end_date', 'start_time', 'end_time', 'date_add', 'user_add', 'res_turn_id'];
   // protected $hidden = ['ms_microsite_id', 'ev_event_id', 'bs_user_id'];
    
    public function turn() {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }
    
}
