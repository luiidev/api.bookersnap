<?php

namespace App;

use App\res_tag_r;
use Illuminate\Database\Eloquent\Model;

class res_reservation extends Model {

    protected $table = "res_reservation";
    public $timestamps = false;
    protected $fillable = ['date_reservation', 'hours_reservation', 'hours_duration', 'num_people', 'note', 'email', 'phone', 'res_guest_id'];
    //protected $hidden = ['ms_microsite_id', 'ev_event_id', 'bs_user_id'];
    
    public function status() {
        return $this->belongsTo('App\res_reservation_status', 'res_reservation_status_id');
    }

    public function guest() {
        return $this->belongsTo('App\res_guest', 'res_guest_id');
    }
    
    public function tables() {
        //return $this->hasOne('App\res_table_reservation');--BIEN
        //return $this->belongsTo('App\res_table_reservation', 'id');
        return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id');
        //return $this->hasMany('App\res_table_reservation', 'res_table_id');
    }
    
    public function tags()
    {
        return $this->belongsToMany(res_tag_r::class, "res_reservation_tag_r", "res_reservation_id");
    }

}
