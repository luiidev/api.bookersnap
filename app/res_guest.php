<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_guest extends Model
{

    protected $table   = "res_guest";
    public $timestamps = false;
    //protected $fillable = ['day', 'res_turn_zone_id', 'res_zone_id', 'ms_microsite_id'];
    protected $hidden = ['ms_microsite_id'];

    public function emails()
    {
        return $this->hasMany('App\res_guest_email', 'res_guest_id');
    }

    public function phones()
    {
        return $this->hasMany('App\res_guest_phone', 'res_guest_id');
    }

    public function tags()
    {
        return $this->belongsToMany('App\res_guest_tag', 'res_guest_has_res_guest_tag', 'res_guest_id', 'res_guest_tag_id');
    }

    public function customsTags()
    {
        return $this->belongsToMany('App\res_guest_tag', 'res_guest_tag_g', 'res_guest_id', 'res_tag_g_id');
    }

}
