<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_reservation_status
 *
 * @author USER
 */
use Illuminate\Database\Eloquent\Model;

class res_reservation_status extends Model {

    const  _ID_RESERVED = 1;
    const  _ID_CONFIRMED = 2;
    const  _ID_WAITING = 3;
    const  _ID_SITTING = 4;
    const  _ID_RELEASED = 5;
    const  _ID_CANCELED = 6;
    const  _ID_ABSENT = 7;
    
    protected $table = "res_reservation_status";
    public $timestamps = false;
    protected $hidden = ['date_add', 'date_upd', 'date_del', 'user_add', 'user_upd', 'user_del'];
    
    
}