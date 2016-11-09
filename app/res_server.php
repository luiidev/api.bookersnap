<?php

namespace App;

use App\res_reservation;
use App\res_table;
use Illuminate\Database\Eloquent\Model;

class res_server extends Model
{
    const CREATED_AT = "date_add";
    const UPDATED_AT = "date_upd";

    protected $table = "res_server";
    protected $visible = ["id", "name", "color", "tables", "reservations"];

    public function tables(){
        return $this->hasMany(res_table::class, "res_server_id");
    }

    public function reservations()
    {
        return $this->hasMany(res_reservation::class, "res_server_id");
    }
}
