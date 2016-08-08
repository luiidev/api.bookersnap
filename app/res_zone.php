<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_zone extends Model {

    protected $table = "res_zone";
    public $timestamps = false;
//    protected $fillable = ['name', 'sketch', 'status', 'type_zone', 'join_table', 'status_smoker', 'people_standing', 'user_add', 'user_upd', 'ev_event_id', 'ms_microsite_id'];
    protected $hidden = ['ms_microsite_id', 'user_add', 'user_upd', 'date_add', 'date_upd'];
    
    public function tables() {
        return $this->hasMany('App\res_table', 'res_zone_id');
    }
    
//    public function turns() {
//        return $this->hasMany('App\res_turn_zone', 'res_zone_id');
//    }
    
}
