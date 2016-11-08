<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_day_turn_promotion extends Model
{
    protected $table = "res_day_turn_promotion";

    public function turn()
    {
        return $this->belongsto('App\Entities\res_turn_promotion');
    }
}
