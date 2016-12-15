<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class bs_country extends Model
{
    protected $table = "bs_country";
    public $timestamps = false;
    public $incrementing = false;

    public function microsites()
    {
    	return $this->hasMany(ms_microsite::class, "bs_country_id");
    }
}
