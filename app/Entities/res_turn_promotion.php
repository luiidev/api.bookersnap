<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_turn_promotion extends Model
{
    protected $table = "res_turn_promotion";

    // public function days()
    // {
    //     return $this->hasMany('App\Entities\res_day_turn_promotion', 'res_turn_id');
    // }

    // public function event()
    // {
    //     return $this->belongsTo('App\Entities\ev_event');
    // }
}
