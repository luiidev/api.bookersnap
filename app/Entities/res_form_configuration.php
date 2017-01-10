<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_form_configuration extends Model
{
    protected $table = "res_form_configuration";
    public $timestamps = false;

    public function form()
    {
    	return $this->belongsTo(res_form::class, "res_form_id");
    }

}
