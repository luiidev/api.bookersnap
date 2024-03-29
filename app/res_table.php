<?php

namespace App;

use App\res_server;
use Illuminate\Database\Eloquent\Model;

class res_table extends Model
{
    protected $table   = "res_table";
    public $timestamps = false;
    protected $hidden  = ['user_add', 'user_upd', 'date_add', 'date_upd', 'pivot'];
    //public $appends = ['newfield', 'estado'];

    /*-------------
    // agregar nuevos atributos , virtual
    --------------*/
    public function getNewfieldAttribute()
    {
        return $this->config_forme * 4;
    }

    public function getEstadoAttribute()
    {
        return $this->status;
    }

    public function turns()
    {
        return $this->hasMany('App\res_turn_table', 'res_table_id');
    }
    
    public function zone()
    {
        return $this->belongsTo('App\res_zone', 'res_zone_id');
    }
    
//    public function turnsZone()
//    {
//        return $this->belongsToMany('App\res_turn', 'res_turn_zone', 'res_zone_id', 'res_turn_id');
//    }

    public function blocks()
    {
        return $this->belongsToMany('App\Entities\Block', 'res_block_table', 'res_table_id', 'res_block_id');
    }

    public function reservations()
    {
        return $this->belongsToMany('App\Entities\Reservation', 'res_table_reservation', 'res_table_id', 'res_reservation_id')->withPivot('num_people');
    }

    public function server()
    {
        return $this->belongsTo(res_server::class, "res_server_id");
    }

}
