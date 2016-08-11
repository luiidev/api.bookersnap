<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_zone_turn extends Model {
    
    protected $table = "res_zone_turn";
    public $timestamps = false;
    protected $hidden = ['ms_microsite_id', 'res_zone_turn_id', 'res_turn_id', 'res_type_turn_id'];    
    
    public function turns() {
       return $this->belongsTo('App\res_turn', 'res_turn_id');
    }
}
