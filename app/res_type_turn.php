<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_type_turn_zone
 *
 * @author USER
 */
use Illuminate\Database\Eloquent\Model;
class res_type_turn extends Model{

    protected $table = "res_type_turn";
    public $timestamps = false;
    protected $fillable = ['id', 'name', 'status'];
        
//    public function turns() {
//        return $this->hasMany('App\res_turn_zone', 'res_type_turn_zone_id');
//    }
}
