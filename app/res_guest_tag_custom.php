<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_guest_tag_custom extends Model
{
    protected $table   = "res_tag_g";
    public $timestamps = false;
    protected $hidden  = ['ms_microsite_id'];

    public function guests()
    {
        return $this->belongsToMany('App\res_guest', 'res_guest_tag_g', 'res_tag_g_id', 'res_guest_id');
    }

}
