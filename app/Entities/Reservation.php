<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\res_reservation_status;
use App\res_source_type;

class Reservation extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_reservation';

    public function tables() {
        return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id')->withPivot('num_people');
    }

    // METODOS SCOPE

    /**
     * 
     * @param type $query
     * @return type
     */
    public function scopeStatusReserved($query) {
        return $query->where("res_reservation_status_id", res_reservation_status::_ID_RESERVED);
    }

    public function scopeSourceWeb($query) {
        return $query->where("res_source_type_id", res_source_type::_ID_WEB);
    }

    public function scopeStanding($query) {
        return $query->where("status_standing", 1);
    }

}
