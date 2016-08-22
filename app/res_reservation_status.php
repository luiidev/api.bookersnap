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

    protected $table = "res_reservation_status";
    public $timestamps = false;
    protected $hidden = ['date_add', 'date_upd', 'date_del', 'user_add', 'user_upd', 'user_del'];
}