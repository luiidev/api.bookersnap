<?php

namespace App\Entities;

use App\Entities\ms_microsite;
use App\Entities\res_configuration;
use Illuminate\Database\Eloquent\Model;

class res_form extends Model
{
    protected $table="res_form";
    protected $hidden = ['pivot'];

    public function configurations(){
        return $this->belongsToMany(res_configuration::class,'res_form_configuration','res_form_id','ms_microsite_id');
    }

    public function microsites()
    {
    	return $this->belongsToMany(ms_microsite::class, "res_form_configuration");
    }
}
