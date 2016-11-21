<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_table_reservation_temp extends Model
{
    protected $table    = "res_table_reservation_temp";
    protected $fillable = ['hour', 'date', 'num_guest', 'zone_id', 'user_id', 'tables_id', 'ev_event_id', 'token', 'expire', 'ms_microsite_id'];
    protected $hidden   = ['id'];
    public $timestamps  = false;
}
