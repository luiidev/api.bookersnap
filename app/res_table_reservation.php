<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_table_reservation extends Model
{
    protected $id      = 'res_reservation_id';
    protected $table   = "res_table_reservation";
    public $timestamps = false;
    //public $appends = ['newfield', 'estado'];

    public function reservation()
    {
        return $this->belongsTo('App\Entities\Reservation', 'res_reservation_id');
        // return $this->belongsTo('App\Entities\Reservation', 'res_reservation_id');
        //return $this->belongsTo('App\res_table','res_table_id');

    }

}
