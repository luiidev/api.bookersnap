<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_turn_time extends Model 
{
    const _TIME_DEFAULT = "01:30:00";

    protected $table = "res_turn_time";
    public $timestamps = false;

}