<?php

namespace App;

use App\res_reservation;
use Illuminate\Database\Eloquent\Model;

class res_tag_r extends Model
{
    protected $table = "res_tag_r";
    protected $fillable = ["name", "ms_microsite_id"];
    public $timestamps = false;

    public function reservations()
    {
        return $this->belongsToMany(res_reservation::class, "res_reservation_tag_r", "res_tag_r_id");
    }
}
