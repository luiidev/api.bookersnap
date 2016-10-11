<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_configuration extends Model
{
    protected $table   = "res_configuration";
    protected $id      = "ms_microsite_id";
    public $timestamps = false;
    protected $hidden  = ['ms_microsite_id'];

    // public function forms()
    // {
    //     return $this->belongsToMany(res_form::class, "res", "res_form_configuration", "ms_microsite_id");
    // }

    // public function microsite()
    // {
    //     return $this->hasOne(ms_microsite::class);
    // }

}
