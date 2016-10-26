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
        return $this->belongsTo('App\Entities\Reservation', 'res_reservation_id')
            ->select('id', 'date_reservation', 'date_reservation', 'res_reservation_status_id', 'hours_duration', 'hours_reservation')
            ->selectRaw("concat(date_reservation,' ',hours_reservation) as hour_initial")
            ->selectRaw("addtime(concat(date_reservation,' ',hours_reservation),hours_duration) as hour_final");
        // return $this->belongsTo('App\Entities\Reservation', 'res_reservation_id');
        //return $this->belongsTo('App\res_table','res_table_id');

    }
}
