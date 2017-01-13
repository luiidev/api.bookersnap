<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Entities\ev_event;
use App\res_zone;
use App\res_reservation;
use App\Services\Helpers\DateTimesHelper;
use App\res_turn_time;

class res_table_reservation_temp extends Model
{
    protected $table    = "res_table_reservation_temp";
    protected $fillable = ['hour', 'date', 'num_guest', 'zone_id', 'user_id', 'tables_id', 'ev_event_id', 'token', 'expire', 'ms_microsite_id', 'next_day', 'standing_people'];
    protected $hidden   = ['id'];
    public $timestamps  = false;
    
    public function event() {
        return $this->belongsTo(ev_event::class, "ev_event_id");
    }
    
    public function zone() {
        return $this->belongsTo(res_zone::class, "zone_id");
    }
}
