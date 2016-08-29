<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_turn_zone extends Model {

    protected $table = "res_turn_zone";
    public $timestamps = false;

//    protected $hidden = ['res_turn_id', 'res_zone_id', 'res_turn_rule__id'];    


    public function turn() {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function rule() {
        return $this->belongsTo('App\res_turn_rule', 'res_turn_rule_id');
    }

    public function zone() {
        return $this->belongsTo('App\res_zone', 'res_zone_id');
    }

}
