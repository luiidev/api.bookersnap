<?php

namespace App;

use App\Entities\ev_event;
use Illuminate\Database\Eloquent\Model;
use App\res_turn;

class res_reservation extends Model {

    const CREATED_AT = "date_add";
    const UPDATED_AT = "date_upd";

    protected $table = "res_reservation";
    protected $hidden = ["ms_microsite_id", "bs_user_id", "date_upd", "user_add"];

    public function status() {
        return $this->belongsTo('App\res_reservation_status', 'res_reservation_status_id');
    }

    public function guest() {
        return $this->belongsTo('App\res_guest', 'res_guest_id');
    }

    public function tables() {
        return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id');
    }

    public function tags() {
        return $this->belongsToMany(res_tag_r::class, "res_reservation_tag_r", "res_reservation_id");
    }

    public function server() {
        return $this->belongsTo(res_server::class, "res_server_id");
    }

    public function source() {
        return $this->belongsTo('App\res_source_type', "res_source_type_id");
    }

    public function turn() {
        return $this->belongsTo('App\res_turn', "res_turn_id");
    }

    public function guestList() {
        return $this->hasMany(res_reservation_guestlist::class);
    }

    public function emails() {
        return $this->hasMany('App\res_reservation_email', "res_reservation_id");
    }

    public function event() {
        return $this->belongsTo(ev_event::class, "ev_event_id");
    }

    public function scopeWithRelations($query) {
        return $query->with(["tables" => function ($query) {
                        return $query->select("res_table.id", "name");
                    }, "guest" => function ($query) {
                        return $query->select("id", "first_name", "last_name")->with("emails", "phones");
                    }, "source", "status", "tags", "turn.typeTurn", "server", "guestList", "emails", "event"]);
    }

    /**
     * Redefinicion de metodo save
     * @param array $options
     */
    public function save(array $options = []) {
        
        // before save code
        $turn = res_turn::TurnReservation($this->ms_microsite_id, $this->date_reservation, $this->hours_reservation)->first();
        $this->res_turn_id = ($turn) ? $turn->id : null;
         
        parent::save();
        // after save code
    }

}
