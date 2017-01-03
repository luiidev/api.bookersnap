<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Entities\ev_event;
use App\res_reservation;
use App\Services\Helpers\DateTimesHelper;
use App\res_turn_time;

class res_table_reservation_temp extends Model
{
    protected $table    = "res_table_reservation_temp";
    protected $fillable = ['hour', 'date', 'num_guest', 'zone_id', 'user_id', 'tables_id', 'ev_event_id', 'token', 'expire', 'ms_microsite_id', 'next_day', 'standing_people'];
    protected $hidden   = ['id'];
    public $timestamps  = false;
    
    public function event() {
        return $this->belongsTo(ev_event::class, "ev_event_id");
    }
    
    /**
     * Redefinicion de metodo save
     * @param array $options
     */
    public function save(array $options = []) {

        // before save code
        $this->beforeSave($options);
        parent::save();
        // after save code
    }
    
    private function beforeSave(array $options = []) {
        
        
        $turn = res_reservation::getTurnReservation($this->ms_microsite_id, $this->date, $this->hour);
        $this->res_turn_id = ($turn) ? $turn->id : null;
        
        $this->date = !empty($this->date) ? trim($this->date) : date("Y-m-d");
        $this->hour = res_reservation::parseHoursReservation(!empty($this->hour) ? trim($this->hour) : date("H:i:s"));
        
        if (is_null($this->hours_duration)) {
            $turn_time = res_turn_time::where("res_turn_id", $this->res_turn_id)->where('num_guests', $this->num_guests)->first();
            $this->hours_duration = ($turn_time) ? $turn_time->time : res_turn_time::_TIME_DEFAULT;
        }
        
        $this->datetime_input = $this->date." ".$this->hour;
        $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
        
        
    }
}
