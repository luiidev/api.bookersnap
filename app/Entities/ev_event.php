<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class ev_event extends Model
{
    protected $table = "ev_event";
    // public function type()
    // {
    //     return $this->belongsTo('App\Entities\bs_type_event');
    // }

    public function turn()
    {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function microsite()
    {
        return $this->belongsTo('App\Entities\ms_microsite');
    }
}
