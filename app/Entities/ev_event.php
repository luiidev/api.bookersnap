<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class ev_event extends Model
{
    protected $table = "ev_event";
    public function type()
    {
        return $this->belongsTo('App\Entities\bs_type_event');
    }

    public function turns()
    {
        return $this->hasMany('App\Entities\res_turn_promotion', 'ev_event_id');
    }
}
