<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_day_turn_zone
 *
 * @author USER
 */
use Illuminate\Database\Eloquent\Model;

class res_day_turn_zone extends Model {

    protected $table = "res_day_turn_zone";
    public $timestamps = false;
    protected $fillable = ['day', 'res_turn_zone_id', 'res_zone_id', 'ms_microsite_id'];
    protected $hidden = ['res_turn_zone_id', 'res_zone_id', 'ms_microsite_id'];

}
