<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_reservation_guestlist extends Model
{
    protected $table = "res_reservation_guestlist";
    public $timestamps = false;

    public function reservation()
    {
        return $this->belongsTo(res_reservation::class, "res_reservation_id");
    }
}
