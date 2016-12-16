<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class ev_event extends Model
{
    protected $table = "ev_event";
    
    protected $hidden = ['ms_microsite_id', 'date_add', 'date_upd', 'date_del', 'user_add', 'user_upd', 'user_del'];
    
    public function type()
    {
        return $this->belongsTo('App\Entities\bs_type_event', 'bs_type_event_id');
    }

    public function turn()
    {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function microsite()
    {
        return $this->belongsTo('App\Entities\ms_microsite');
    }

    public function turns()
    {
        return $this->hasMany('App\Entities\res_turn_promotion', 'ev_event_id');
    }
}
