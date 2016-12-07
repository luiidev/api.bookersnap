<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use App\res_reservation_guestlist;
use App\res_turn_time;
use App\Services\Helpers\DateTimesHelper;
use App\Services\Helpers\TurnsHelper;
use Carbon\Carbon;

class TableReservationService extends Service
{
    private $guest;
    protected $_ID_SOURCE_RESERVATION_HOSTESS = 1;
    protected $_ID_STATUS_RESERVATION_RESERVED = 1;
    protected $_ID_STATUS_RESERVATION_SEATED = 4;
    protected $_ID_STATUS_RESERVATION_RELEASED = 5;

    public function find_guest()
    {
        $this->guest = res_guest::find($this->req->guest_id);
    }

    public function create_guest()
    {
        $guest             = new res_guest();
        $guest->first_name = $this->req->guest["first_name"];
        if (isset($this->req->guest["last_name"])) {
            $guest->last_name = $this->req->guest["last_name"];
        }

        $guest->user_add        = $this->req->_bs_user_id;
        $guest->ms_microsite_id = $this->microsite_id;

        $guest->save();

        $this->guest = $guest;
    }

    public function create_guest_email()
    {
        $guest_email        = new res_guest_email();
        $guest_email->email = $this->req->guest["email"];

        $this->guest->emails()->save($guest_email);
    }

    public function create_guest_phone()
    {
        $guest_phone         = new res_guest_phone();
        $guest_phone->number = $this->req->guest["phone"];

        $this->guest->phones()->save($guest_phone);
    }

    private function create_guest_case()
    {
        if ($this->req->has("guest_id")) {
            $this->find_guest();
        } else {
            if ($this->req->has("guest.first_name")) {
                $this->create_guest();

                if ($this->req->has("guest.email")) {
                    $this->create_guest_email();
                }

                if ($this->req->has("guest.phone")) {
                    $this->create_guest_phone();
                }
            }
        }
    }

    private function update_input_output(&$reservation)
    {
        $now = Carbon::now();
        $reservation->datetime_input  = $now->toDateTimeString();
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input,$reservation->hours_duration);
    }

    private function save_reservation(res_reservation $reservation, $action)
    {
        $this->create_guest_case();

        $email = $phone = $guest_id = null;
        if (isset($this->guest)) {
            $guest_id = $this->guest->id;
            if ($this->guest->emails->count()) {
                $email = $this->guest->emails->first()->email;
            }
            if ($this->guest->phones->count()) {
                $phone = $this->guest->phones->first()->number;
            }
        }

        $now     = Carbon::now();
        $turn = TurnsHelper::TypeTurnWithHourForHour($this->req->date, $this->req->hour, $this->microsite_id);

        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = $this->_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = $this->req->status_id;
        $reservation->status_released           = 0;
        $reservation->num_guest                 = $this->req->covers;
        $reservation->date_reservation          = $this->req->date;
        $reservation->hours_reservation         = $this->req->hour;
        $reservation->hours_duration            = $this->req->duration;
        $reservation->res_server_id             = $this->req->server_id;
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        $reservation->res_turn_id               = $turn->turn_id;

        if ($this->req->status_id < $this->_ID_STATUS_RESERVATION_SEATED || $this->req->status_id > $this->_ID_STATUS_RESERVATION_RELEASED) {
            if ($action == "create") {
                $reservation->datetime_input  = trim($this->req->date) . ' ' . trim($this->req->hour);
                $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input,$this->req->duration);
            }
        } else if ($this->req->status_id == $this->_ID_STATUS_RESERVATION_SEATED) {
            $reservation->datetime_input  = $now->toDateTimeString();
            $duration                     = $action == "create" ? $this->req->duration : $reservation->hours_duration;
            $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $duration);
        } else if ($this->req->status_id == $this->_ID_STATUS_RESERVATION_RELEASED) {
            $reservation->datetime_output = $now->toDateTimeString();
        }

        $reservation->save();

        $tables = array();
        foreach ($this->req->tables as $key => $value) {
            $tables[$value] = array("num_people" => 0);
        }

        $reservation->tables()->sync($tables);

        if ($this->req->has("tags")) {
            $reservation->tags()->sync($this->req->tags);
        }

        //  Wait List clear tables
        if ($reservation->wait_list === 1 && $this->req->status_id != $this->_ID_STATUS_RESERVATION_SEATED && $this->req->status_id != $this->_ID_STATUS_RESERVATION_RELEASED) {
            $reservation->tables()->sync([]);
        }

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }

    public function create_reservation()
    {
        $reservation = new res_reservation();

        return $this->save_reservation($reservation, "create");
    }

    public function edit()
    {
        $get = array(
            "id",
            "res_guest_id",
            "res_reservation_status_id",
            "date_reservation",
            "hours_reservation",
            "hours_duration",
            "num_guest",
            "note",
            "res_server_id",
        );

        return res_reservation::select($get)->withRelations()->where("ms_microsite_id", $this->microsite_id)->find($this->reservation);
    }

    public function update()
    {
        $reservation = res_reservation::find($this->reservation);
        $data        = $this->save_reservation($reservation, "update");

        return array($data);
    }

    public function cancel()
    {
        res_reservation::where("id", $this->reservation)->update(["res_reservation_status_id" => 6]);
        $data = res_reservation::withRelations()->find($this->reservation);

        return array($data);
    }

    public function quickEdit()
    {
        
        $reservation = res_reservation::find($this->reservation);

        $reservation->num_guest                 = $this->req->covers;
        $reservation->res_server_id             = $this->req->server_id;
        $reservation->note                      = $this->req->note;
        $reservation->num_people_1              = $this->req->guests["men"];
        $reservation->num_people_2              = $this->req->guests["women"];
        $reservation->num_people_3              = $this->req->guests["children"];
        $reservation->res_reservation_status_id = $this->req->status_id;

        if ($this->req->status_id == $this->_ID_STATUS_RESERVATION_SEATED) {
            $this->update_input_output($reservation);
        } else if ($this->req->status_id == $this->_ID_STATUS_RESERVATION_RELEASED) {
            $now = Carbon::now();
            $reservation->datetime_output = $now->toDateTimeString();
        }

        $reservation->save();

        //  Wait List clear tables
        if ($reservation->wait_list === 1 && $this->req->status_id != $this->_ID_STATUS_RESERVATION_SEATED) {
            $reservation->tables()->sync([]);
        }

        if ($this->req->has("tags")) {
            $reservation->tags()->sync($this->req->tags);
        }

        $data = res_reservation::withRelations()->find($this->reservation);

        return array($data);
    }

    public function quickCreate()
    {
        $now  = Carbon::now();
        $date = $now->toDateString();
        $time = DateTimesHelper::RoundBeforeTime($now->toTimeString());
        $datetime_input = $now->toDateTimeString();
                
        $num_guest = $this->req->guests["men"] + $this->req->guests["women"] + $this->req->guests["children"];
        // if (@$this->req->guests["total"]) {
        //     $num_guest = ($num_guest == 0) ? $this->req->guests["total"] : $num_guest;
        // }

        $turn     = TurnsHelper::TypeTurnWithHourForHour($date, $time, $this->microsite_id);
        $duration = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $num_guest)->first();

        $reservation                            = new res_reservation();
        $reservation->res_source_type_id        = $this->_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = $this->_ID_STATUS_RESERVATION_SEATED;
        $reservation->status_released           = 0;
        $reservation->num_guest                 = $num_guest;
        $reservation->num_people_1              = $this->req->guests["men"];
        $reservation->num_people_2              = $this->req->guests["women"];
        $reservation->num_people_3              = $this->req->guests["children"];
        $reservation->date_reservation          = $date;
        $reservation->hours_reservation         = $time;
        $reservation->hours_duration            = $duration ? $duration->time : "01:30:00";
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        $reservation->res_turn_id               = $turn->turn_id;

        $reservation->datetime_input  = $datetime_input;
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input,$reservation->hours_duration);

        $reservation->save();

        $reservation->tables()->attach($this->req->table_id, ["num_people" => $num_guest]);

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }

    public function sit()
    {
        $now = Carbon::now();

        $reservation = res_reservation::withCount(['tables' => function ($query) {
            $query->where('res_table_id', $this->req->table_id);
        }])->where("id", $this->reservation)->first();

        if ($reservation !== null) {

            // Actualizar reservacion
            $reservation->res_reservation_status_id = $this->_ID_STATUS_RESERVATION_SEATED;
            $this->update_input_output($reservation);

            if ($this->req->has("guests")) {
                // Por consultar actualizacion de  numero de invitados
                // $num_guest = $this->req->guests["men"] + $this->req->guests["women"] + $this->req->guests["children"];
                // $reservation->num_guest = $num_guest;
                $reservation->num_people_1 = $this->req->guests["men"];
                $reservation->num_people_2 = $this->req->guests["women"];
                $reservation->num_people_3 = $this->req->guests["children"];
            }

            $reservation->save();

            // Actualizar mesa en caso se siente en otra mesa diferente a la de su reservacion
            if ($reservation->tables_count === 0) {
                $reservation->tables()->sync([$this->req->table_id => ["num_people" => 0]]);
            }
            // end

            // Actualizar otras reservaciones que estasn ocupando la mesa
            $others_reservation = res_reservation::withCount(['tables' => function ($query) {
                $query->where('res_table_id', $this->req->table_id);
            }])->where("id", "<>", $this->reservation)
                ->where("date_reservation", $now->toDateString())
                ->where("res_reservation_status_id", "=", $this->_ID_STATUS_RESERVATION_SEATED)
                ->where("ms_microsite_id", $this->microsite_id)
                ->get();

            $filtered = $others_reservation->filter(function ($item) {
                return $item['tables_count'] > 0;
            });

            if ($others_reservation->count()) {
                res_reservation::whereIn("id", $filtered->pluck("id"))
                    ->update([
                        "res_reservation_status_id" => $this->_ID_STATUS_RESERVATION_RELEASED,
                        "datetime_output"           => $now->toDateTimeString(),
                    ]);
            }
            // end

            $data = res_reservation::where("id", $reservation->id)
                ->orwhereIn("id", $filtered->pluck("id"))
                ->withRelations()
                ->get();
            return $data;
        }

        return $reservation;
    }

    public function updateGuestList()
    {
        $guest_list_add = array();
        $people_1       = 0;
        $people_2       = 0;
        $people_3       = 0;
        $total          = 0;

        $sumPeople = function ($person) use (&$people_1, &$people_2, &$people_3) {
            if ($person === 1) {
                $people_1++;
            } else if ($person === 2) {
                $people_2++;
            } else if ($person === 3) {
                $people_3++;
            }
        };

        foreach ($this->req->guest_list as $key => $guest) {
            $sumPeople($guest["type_person"]);
            if ($guest["arrived"]) {
                $total++;
            }

            res_reservation_guestlist::where("id", $guest["id"])
                ->where("res_reservation_id", $this->reservation)
                ->update([
                    "arrived"     => $guest["arrived"],
                    "type_person" => $guest["type_person"],
                    "status"      => $guest["status"],
                ]);
        }

        if ($this->req->has("guest_list_add")) {
            foreach ($this->req->guest_list_add as $key => $guest_add) {
                $sumPeople($guest_add["type_person"]);
                if ($guest_add["arrived"]) {
                    $total++;
                }

                $guest              = new res_reservation_guestlist();
                $guest->name        = $guest_add["name"];
                $guest->arrived     = $guest_add["arrived"];
                $guest->type_person = $guest_add["type_person"];
                array_push($guest_list_add, $guest);
            }
        }

        $reservation = res_reservation::find($this->reservation);

        if ($people_1 > $reservation->num_people_1) {
            $reservation->num_people_1 = $people_1;
        }

        if ($people_2 > $reservation->num_people_2) {
            $reservation->num_people_2 = $people_2;
        }

        if ($people_3 > $reservation->num_people_3) {
            $reservation->num_people_3 = $people_3;
        }

        if ($total > $reservation->num_guest) {
            $reservation->num_guest = $total;
        }

        $reservation->save();

        $reservation->guestList()->saveMany($guest_list_add);

        $data = res_reservation::withRelations()->find($this->reservation);
        return array($data);
    }

    public function create_waitlist()
    {

        $this->create_guest_case();
        $email = $phone = $guest_id = null;

        if (isset($this->guest)) {
            $guest_id = $this->guest->id;
            if ($this->guest->emails->count()) {
                $email = $this->guest->emails->first()->email;
            }
            if ($this->guest->phones->count()) {
                $phone = $this->guest->phones->first()->number;
            }
        }
        
        $now = Carbon::now();
        $date_reservation  = $now->toDateString();
        $datetime_reservation  = DateTimesHelper::AddTime($now->toDateTimeString(), $this->req->quote);
        $date = \Carbon\Carbon::parse($datetime_reservation);
        $hours_reservation = DateTimesHelper::RoundBeforeTime($date->toTimeString());
        $datetime_input = \Carbon\Carbon::parse($date_reservation." ".$hours_reservation)->toDateTimeString();
        
        $turn     = TurnsHelper::TypeTurnWithHourForHour($date_reservation, $hours_reservation, $this->microsite_id);
        $duration = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $this->req->covers)->first();
        $hours_duration = $duration ? $duration->time : "01:30:00";

        $reservation                            = new res_reservation();
        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = 1;
        $reservation->res_reservation_status_id = 1;
        $reservation->status_released           = 0;
        $reservation->wait_list                 = 1;
        $reservation->date_reservation          = $date_reservation;
        $reservation->num_guest                 = $this->req->covers;
        $reservation->hours_reservation         = $hours_reservation;
        $reservation->hours_duration            = $hours_duration;
        $reservation->quote                     = $this->req->quote;
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        $reservation->res_turn_id               = $turn->turn_id;
        
        $reservation->datetime_input  = $datetime_input;
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input,$reservation->hours_duration);
        
        $reservation->save();

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }

    public function update_waitlist()
    {

        $this->create_guest_case();
        $email = $phone = $guest_id = null;

        if (isset($this->guest)) {
            $guest_id = $this->guest->id;
            if ($this->guest->emails->count()) {
                $email = $this->guest->emails->first()->email;
            }
            if ($this->guest->phones->count()) {
                $phone = $this->guest->phones->first()->number;
            }
        }
        
        $reservation                            = res_reservation::where('id', $this->req->id)->first();
        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = $this->_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = $this->_ID_STATUS_RESERVATION_RESERVED;
        $reservation->status_released           = 0;
        $reservation->wait_list                 = 1;              
        $reservation->num_guest                 = $this->req->covers;        
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        
        $datetime_reservation = $reservation->date_reservation. " ". $reservation->hours_reservation;
        $datetime_reservation  = DateTimesHelper::DiffTime($reservation->date_add, $reservation->quote);
        $datetime_reservation  = DateTimesHelper::AddTime($datetime_reservation, $this->req->quote);
        $date = \Carbon\Carbon::parse($datetime_reservation);
        
        $reservation->hours_reservation         = $date->toTimeString();        
        $reservation->quote                     = $this->req->quote;
        
        $turn     = TurnsHelper::TypeTurnWithHourForHour($reservation->date_reservation, $reservation->hours_reservation, $this->microsite_id);
        $duration = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $this->req->covers)->first();
        $hours_duration = $duration ? $duration->time : "01:30:00";
        
        $reservation->hours_duration            = $hours_duration;
        $reservation->datetime_input  = $datetime_reservation;
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);
        
        $reservation->save();

        //  Wait List clear tables
        if ($this->req->status_id != 4) {
            $reservation->tables()->sync([]);
        }

        $data = res_reservation::withRelations()->find($reservation->id);

        return array($data);
    }

    public function delete_waitList()
    {
        $reservation = res_reservation::where('id', $this->req->id)->where('ms_microsite_id', $this->microsite_id)->first();

        $reservation->res_reservation_status_id = 6;

        $reservation->save();

        $reservation->tables()->sync([]);

        $data = res_reservation::withRelations()->find($this->req->id);

        return array($data);
    }
}
