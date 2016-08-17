<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_guest extends Model {

    protected $table = "res_guest";
    public $timestamps = false;
    //protected $fillable = ['day', 'res_turn_zone_id', 'res_zone_id', 'ms_microsite_id'];
    protected $hidden = ['ms_microsite_id'];

    public function email() {
       return $this->hasMany('App\res_guest_email', 'res_guest_id');
    }

    public function phone() {
       return $this->hasMany('App\res_guest_phone', 'res_guest_id');
    }

}
