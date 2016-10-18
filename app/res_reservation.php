<?php

namespace App;

use App\res_server;
use App\res_tag_r;
use Illuminate\Database\Eloquent\Model;

class res_reservation extends Model {

    const CREATED_AT = "date_add";
    const UPDATED_AT = "date_upd";

    protected $table = "res_reservation";
    protected $fillable = ['date_reservation', 'hours_reservation', 'hours_duration', 'num_people', 'note', 'email', 'phone', 'res_guest_id'];
    
    public function status() {
        return $this->belongsTo('App\res_reservation_status', 'res_reservation_status_id');
    }

    public function guest() {
        return $this->belongsTo('App\res_guest', 'res_guest_id');
    }
    
    public function tables() {
        return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id');
    }
    
    public function tags()
    {
        return $this->belongsToMany(res_tag_r::class, "res_reservation_tag_r", "res_reservation_id");
    }

    public function server()
    {
        return $this->belongsTo(res_server::class, "res_server_id");
    }

}
