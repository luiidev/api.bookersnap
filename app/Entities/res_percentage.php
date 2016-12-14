<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_percentage extends Model
{
    protected $table   = "res_percentage";
    protected $primaryKey      = "res_percentage";
    public $timestamps = false;

    public function microsites()
    {
    	return $this->hasMany(ms_microsite::class, "res_percentage_id");
    }
}
