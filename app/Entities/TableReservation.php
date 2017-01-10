<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class TableReservation extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_table_reservation';

}
