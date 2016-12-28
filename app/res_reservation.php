<?php

namespace App;

use App\Entities\ev_event;
use Illuminate\Database\Eloquent\Model;
use App\res_turn;
use App\res_reservation_status;
use App\res_source_type;
use App\res_turn_time;
use DB;
use Carbon\Carbon;
use App\Services\Helpers\DateTimesHelper;

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
        $this->beforeSave($options);
        parent::save();
        // after save code
    }

    private function beforeSave(array $options = []) {
        
        $reservation = res_reservation::find($this->id);
        
        $turn = self::getTurnReservation($this->ms_microsite_id, $this->date_reservation, $this->hours_reservation);
        $this->res_turn_id = ($turn) ? $turn->id : null;
        
        $this->date_reservation = !empty($this->date_reservation) ? trim($this->date_reservation) : date("Y-m-d");
        $this->hours_reservation = self::parseHoursReservation(!empty($this->hours_reservation) ? trim($this->hours_reservation) : date("H:i:s"));
        
        if (is_null($this->hours_duration)) {
            $turn_time = res_turn_time::where("res_turn_id", $this->res_turn_id)->where('num_guests', $this->num_guests)->first();
            $this->hours_duration = ($turn_time) ? $turn_time->time : res_turn_time::_TIME_DEFAULT;
        }
        
        if($reservation){
            $this->setDatetimesReservationUpdate($reservation);
        }else{
            $this->setDatetimesReservationCreate();
        }
        
    }

    /**
     * Calcular la fecha y hora real de entrada y salida.
     * @param int $res_reservation_status
     * @param string $date_reservation
     * @param string $hours_reservation
     */
    private function setDatetimesReservationCreate() {
                    
        $datetime_reservation = trim($this->date_reservation)." ".trim($this->hours_reservation);
        $this->datetime_input = $datetime_reservation;
        
        switch ($this->res_reservation_status_id) {
            case res_reservation_status::_ID_RESERVED:
            case res_reservation_status::_ID_CONFIRMED:
            case res_reservation_status::_ID_WAITING:
            case res_reservation_status::_ID_CANCELED:
            case res_reservation_status::_ID_ABSENT:
                list($hour, $minute) = explode(":", $this->hours_duration);
                $this->datetime_output = Carbon::parse($this->datetime_input)->addHours($hour)->addMinutes($minute);
                break;

            case res_reservation_status::_ID_SITTING:
                $this->datetime_input = Carbon::now()->toDateTimeString();
                $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
                break;

            case res_reservation_status::_ID_RELEASED:
                $this->datetime_input = Carbon::now()->toDateTimeString();
                $this->datetime_output = $this->datetime_input;
                break;

            default:
                $this->res_reservation_status_id = res_reservation_status::_ID_RESERVED;
                $this->datetime_input = $datetime_reservation;
                $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
                break;
        }
        
    }
    private function setDatetimesReservationUpdate($reservation) {
                    
        $datetime_reservation = trim($this->date_reservation)." ".trim($this->hours_reservation);
        
        if($reservation->res_reservation_status_id != $this->res_reservation_status_id){
            switch ($this->res_reservation_status_id) {
                case res_reservation_status::_ID_RESERVED:
                case res_reservation_status::_ID_CONFIRMED:
                case res_reservation_status::_ID_WAITING:
                    $this->datetime_input = $datetime_reservation;
                    $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
                    break;

                case res_reservation_status::_ID_SITTING:
                    $this->datetime_input = Carbon::now()->toDateTimeString();
                    $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);

                    break;

                case res_reservation_status::_ID_RELEASED:
                    $this->datetime_output = Carbon::now()->toDateTimeString();
                    break;

                case res_reservation_status::_ID_CANCELED:
                case res_reservation_status::_ID_ABSENT:
                    $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
                    break;

                default:
                    $this->res_reservation_status_id = res_reservation_status::_ID_RESERVED;
                    $this->datetime_input = $datetime_reservation;
                    $this->datetime_output = DateTimesHelper::AddTime($this->datetime_input, $this->hours_duration);
                    break;
            }
        }
    }
    
    /**
     * Obtener el turno para una reservacion en un micrositio en una fecha y hora determinada
     * @param int $microsite_id
     * @param string $date_reservation
     * @param string $hours_reservation
     * @return \App\res_turn|false
     */
    public static function getTurnReservation(int $microsite_id, string $date_reservation = null, string $hours_reservation = null) {

        $date_reservation = !is_null($date_reservation) ? $date_reservation : date("Y-m-d");
        $hours_reservation = !is_null($hours_reservation) ? $hours_reservation : date("H:i:s");

        $indexHoursReservation = strtotime($hours_reservation);
        $turns = res_turn::TurnReservation($microsite_id, $date_reservation)->get()->map(function($item) use ($indexHoursReservation) {
            $indexIni = strtotime($item->hours_ini);
            $indexEnd = strtotime($item->hours_end);
            $indexEnd = ($indexEnd > $indexIni) ? $indexEnd : ($indexEnd + 24 * 60 * 60 * 1000);

            $indexlimitIni = strtotime("00:00:00");
            $indexlimitEnd = strtotime("05:00:00");
            $indexReservation = !($item->hours_end < $item->hours_ini && ($indexHoursReservation >= $indexlimitIni && $indexHoursReservation <= $indexlimitEnd)) ? $indexHoursReservation : ($indexHoursReservation + 24 * 60 * 60 * 1000);

            $item->index_ini = $indexIni;
            $item->index_end = $indexEnd;
            $item->index_reservation = $indexReservation;
            return $item;
        });

        $time = strtotime($hours_reservation);

        $turn = $turns->reject(function($value, $k) {
                    $indexReservationInRange = ($value->index_reservation >= $value->index_ini && $value->index_reservation <= $value->index_end);
                    return !$indexReservationInRange;
                })->first();

        if (!$turn) {
            $turn = $turns->reject(function($value, $k) {
                        $indexReservationLeftIndexEnd = ($value->index_reservation <= $value->index_end);
                        return !$indexReservationLeftIndexEnd;
                    })->first();
        }
        return $turn;
    }

    /**
     * Convevierte un Time a formato de Hora de reservacion
     * @param string $time  Formato Time
     * @return string       Time con redondeo de 15 min
     */
    public static function parseHoursReservation(string $time = null) {
        $time = !is_null($time) ? $time : date("H:i:s");
        list($hours, $minutes) = explode(":", $time);
        $minutes = $minutes - ($minutes % 15);
        $time = $hours . ":" . str_pad($minutes, 2, "0", STR_PAD_LEFT).":00";
        return $time;
    }

}
