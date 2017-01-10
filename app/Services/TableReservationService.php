<?php

namespace App\Services;

use App\Entities\ms_microsite;
use App\Entities\res_table_reservation_temp;
use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use App\res_reservation_guestlist;
use App\res_table_reservation;
use App\res_turn_time;
use App\Services\Helpers\CalendarHelper;
use App\Services\Helpers\DateTimesHelper;
use App\Services\Helpers\TurnsHelper;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Encryption\DecryptException;

class TableReservationService extends Service
{
    private $guest;

    const _ID_SOURCE_RESERVATION_HOSTESS  = 1;
    const _ID_SOURCE_RESERVATION_WEB      = 4;
    const _ID_STATUS_RESERVATION_RESERVED = 1;
    const _ID_STATUS_RESERVATION_SEATED   = 4;
    const _ID_STATUS_RESERVATION_RELEASED = 5;
    const _ID_STATUS_RESERVATION_CANCELED = 6;
    const _ID_STATUS_RESERVATION_ABSENT   = 7;

    public function showByCrypt()
    {
        try {
            $id = decrypt($this->crypt);

            return res_reservation::WithRelations()->with('microsite')
                ->where("res_reservation_status_id", self::_ID_STATUS_RESERVATION_RESERVED)
                ->where("ms_microsite_id", $this->microsite_id)
                ->where("id", $id)
                ->first();
        } catch (DecryptException $e) {
            return "";
        }
    }

    public function cancelReserveWeb()
    {
        try {
            $id = decrypt($this->crypt);
            
            res_reservation::where("res_reservation_status_id", self::_ID_STATUS_RESERVATION_RESERVED)
                ->where("ms_microsite_id", $this->microsite_id)
                ->where("id", $id)
                ->update([
                    "res_reservation_status_id" => self::_ID_STATUS_RESERVATION_CANCELED,
                ]);
            
            return res_reservation::WithRelations()
                ->where("ms_microsite_id", $this->microsite_id)
                ->where("id", $id)
                ->first();
            
        } catch (DecryptException $e) {
            return "";
        }
    }

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

        if ($this->req->has("profession")) {
            $guest->profession = $this->req->guest["profession"];
        }

        if ($this->req->has("find_out")) {
            $guest->find_out = $this->req->guest["find_out"];
        }

        if (isset($this->req->guest["birthdate"])) {
            $guest->birthdate = $this->req->guest["birthdate"];
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
        $now                          = Carbon::now();
        $reservation->datetime_input  = $now->toDateTimeString();
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);
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

        $now = Carbon::now();
        //$turn = TurnsHelper::TypeTurnWithHourForHour($this->req->date, $this->req->hour, $this->microsite_id);
        $reservationInit = CalendarHelper::CalculeTimesReservation($this->microsite_id, $this->req->date, $this->req->hour);
        if (!$reservationInit) {
            abort(500, "No hay reservaciones para esta fecha");
        }
        //$duration                     = ($action == "create") ? $this->req->duration : $reservation->hours_duration;

        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = self::_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = $this->req->status_id;
        $reservation->status_released           = 0;
        $reservation->num_guest                 = $this->req->covers;
        $reservation->num_people_1              = $this->req->guests["men"];
        $reservation->num_people_2              = $this->req->guests["women"];
        $reservation->num_people_3              = $this->req->guests["children"];
        $reservation->date_reservation          = $this->req->date;
        $reservation->hours_reservation         = $reservationInit->hours_reservation;
        $reservation->hours_duration            = $this->req->duration;
        $reservation->res_server_id             = $this->req->server_id;
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
//        $reservation->res_turn_id               = $reservationInit->res_turn_id;

//        if ($this->req->status_id < self::_ID_STATUS_RESERVATION_SEATED) {
        ////            if ($action == "create") {
        //                $reservation->datetime_input  = trim($reservationInit->date_reservation) . ' ' . trim($reservation->hours_reservation);
        //                $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);
        ////            }
        //        } else if ($this->req->status_id == self::_ID_STATUS_RESERVATION_SEATED) {
        //            $reservation->datetime_input  = $now->toDateTimeString();
        //            $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);
        //        } else if ($this->req->status_id == self::_ID_STATUS_RESERVATION_RELEASED || $this->req->status_id == self::_ID_STATUS_RESERVATION_CANCELED || $this->req->status_id == self::_ID_STATUS_RESERVATION_ABSENT) {
        //            $reservation->datetime_input  = ($action == "create") ? $now->toDateTimeString():$reservation->datetime_input;
        //            $reservation->datetime_output = $now->toDateTimeString();
        //        }

        $reservation->status_standing = (is_array($this->req->tables) && count($this->req->tables) > 0) ? 0 : 1;

        $reservation->save();

        if (is_array($this->req->tables)) {
            $tables = array();
            foreach ($this->req->tables as $value) {
                $tables[$value] = array("num_people" => 0);
            }
            $reservation->tables()->sync($tables);
        }

        if ($this->req->has("tags")) {
            $reservation->tags()->sync($this->req->tags);
        }

        //  Wait List clear tables
        if ($reservation->wait_list === 1 && $this->req->status_id != self::_ID_STATUS_RESERVATION_SEATED && $this->req->status_id != self::_ID_STATUS_RESERVATION_RELEASED) {
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

        $reservation = res_reservation::select($get)->withRelations()->where("ms_microsite_id", $this->microsite_id)->find($this->reservation);
        return $reservation;
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

        if ($this->req->status_id == self::_ID_STATUS_RESERVATION_SEATED) {
            $this->update_input_output($reservation);
        } else if ($this->req->status_id == self::_ID_STATUS_RESERVATION_RELEASED || $this->req->status_id == self::_ID_STATUS_RESERVATION_CANCELED || $this->req->status_id == self::_ID_STATUS_RESERVATION_ABSENT) {
            $now                          = Carbon::now();
            $reservation->datetime_output = $now->toDateTimeString();
        }

        $reservation->save();

        //  Wait List clear tables
        if ($reservation->wait_list === 1 && $this->req->status_id != self::_ID_STATUS_RESERVATION_SEATED) {
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
        $num_guest = $this->req->guests["men"] + $this->req->guests["women"] + $this->req->guests["children"];
        // if (@$this->req->guests["total"]) {
        //     $num_guest = ($num_guest == 0) ? $this->req->guests["total"] : $num_guest;
        // }
        $reservationInit = CalendarHelper::CalculeTimesReservationNow($this->microsite_id);

        if (!$reservationInit) {
            abort(500, "No puedes crear reservaciones rápidas en este tiempo");
        }

        $realDate = CalendarHelper::realDate($this->microsite_id);
//        $turn     = TurnsHelper::TypeTurnWithHourForHour($realDate, $time, $this->microsite_id);
        $duration = res_turn_time::where("res_turn_id", $reservationInit->res_turn_id)->where("num_guests", $num_guest)->first();

        $reservation                            = new res_reservation();
        $reservation->res_source_type_id        = self::_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = self::_ID_STATUS_RESERVATION_SEATED;
        $reservation->status_released           = 0;
        $reservation->num_guest                 = $num_guest;
        $reservation->num_people_1              = $this->req->guests["men"];
        $reservation->num_people_2              = $this->req->guests["women"];
        $reservation->num_people_3              = $this->req->guests["children"];
        $reservation->date_reservation          = $realDate;
        $reservation->hours_reservation         = $reservationInit->hours_reservation;
        $reservation->hours_duration            = $duration ? $duration->time : "01:30:00";
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        $reservation->res_turn_id               = $reservationInit->res_turn_id;

//        $reservation->datetime_input  = Carbon::now()->toDateTimeString();
        $reservation->datetime_input  = $reservationInit->date_reservation . " " . $reservationInit->hours_reservation;
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);

        $reservation->save();

        $reservation->tables()->attach($this->req->table_id, ["num_people" => $num_guest]);

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }

    public function releasedReservations(int $microsite_id, int $reservation_id, int $table_id)
    {

        $realDate      = CalendarHelper::realDate($microsite_id);
        $datetimeOpen  = CalendarHelper::realDateTimeOpen($microsite_id, $realDate);
        $datetimeClose = CalendarHelper::realDateTimeClose($microsite_id, $realDate);
        /* Liberar reservaciones que estan ocupando esta mesa */
        $resId = res_reservation::join('res_table_reservation', "res_reservation.id", "=", "res_table_reservation.res_reservation_id")
            ->where('res_reservation.ms_microsite_id', $microsite_id)
            ->whereRaw("CONCAT(res_reservation.date_reservation, ' ', res_reservation.hours_reservation) BETWEEN ? AND ?", array($datetimeOpen, $datetimeClose))
            ->where('res_table_reservation.res_reservation_id', '!=', $reservation_id)
            ->where('res_table_reservation.res_table_id', $table_id)
            ->where('res_reservation.res_reservation_status_id', self::_ID_STATUS_RESERVATION_SEATED)
            ->pluck('res_reservation.id');

        $result = res_reservation::whereIn('id', $resId)->update(['res_reservation_status_id' => self::_ID_STATUS_RESERVATION_RELEASED]);

        if ($result > 0) {
            return res_reservation::withRelations()->whereIn('id', $resId)->get();
        }
        return false;
    }

    public function sit()
    {
        $now = Carbon::now();

        $reservation = res_reservation::withCount(['tables' => function ($query) {
            $query->where('res_table_id', $this->req->table_id);
        }])->where("id", $this->reservation)->first();

        if ($reservation !== null) {

            // Actualizar reservacion
            if ($reservation->res_reservation_status_id != self::_ID_STATUS_RESERVATION_SEATED) {
                $reservation->res_reservation_status_id = self::_ID_STATUS_RESERVATION_SEATED;
                $this->update_input_output($reservation);
            }

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
                ->where("res_reservation_status_id", "=", self::_ID_STATUS_RESERVATION_SEATED)
                ->where("ms_microsite_id", $this->microsite_id)
                ->get();

            $filtered = $others_reservation->filter(function ($item) {
                return $item['tables_count'] > 0;
            });

            if ($others_reservation->count()) {
                res_reservation::whereIn("id", $filtered->pluck("id"))
                    ->update([
                        "res_reservation_status_id" => self::_ID_STATUS_RESERVATION_RELEASED,
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

    public function storeFromWeb($token)
    {
        
        $now = Carbon::now();
        $temporal = res_table_reservation_temp::where("token", $token)->where("expire", ">", $now)->orderBy("id", "desc")->first();

        if ($temporal === null) {
            abort(422, "La reservacion no existe o expiro el tiempo de reservacion.");
        }
        
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

        $reservation                            = new res_reservation();
        $reservation->res_guest_id              = $guest_id;
        $reservation->ev_event_id               = $temporal->ev_event_id;
        $reservation->res_source_type_id        = self::_ID_SOURCE_RESERVATION_WEB;
        $reservation->res_reservation_status_id = self::_ID_STATUS_RESERVATION_RESERVED;
        $reservation->status_released           = 0;        
        $reservation->num_guest                 = $temporal->num_guest;
        $reservation->date_reservation          = $temporal->date;
        $reservation->hours_reservation         = $temporal->hour;
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;      
        $reservation->status_standing = (strlen(trim($temporal->tables_id))>0)?0:1;
        
        $reservation->save();
                
        // Tables
        $tables = explode(",", trim($temporal->tables_id));        
        foreach ($tables as $value) {
            $tables[$value] = array("num_people" => 0);
        }
        $reservation->tables()->sync($tables);

        // Guest List
        $guest_list_add = array();
        if ($this->req->has("guest_list")) {
            foreach ($this->req->guest_list as $name) {
                $guest          = new res_reservation_guestlist();
                $guest->name    = $name;
                $guest->arrived = 0;
                array_push($guest_list_add, $guest);
            }
        }
        $reservation->guestList()->saveMany($guest_list_add);

        res_table_reservation_temp::where("token", $token)->where("expire", ">", $now)->update(["expire" => $now]);

        $data = array(
            "reservation" => res_reservation::withRelations()->find($reservation->id),
            "site"        => ms_microsite::with("country", "configuration.percentage")->find($this->microsite_id),
            "reserve_key" => encrypt($reservation->id),
        );

        return $data;
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

        $now                  = Carbon::now();
        $date_reservation     = $now->toDateString();
        $datetime_reservation = DateTimesHelper::AddTime($now->toDateTimeString(), $this->req->quote);
        $date                 = \Carbon\Carbon::parse($datetime_reservation);
        $hours_reservation    = DateTimesHelper::RoundBeforeTime($date->toTimeString());
        $datetime_input       = \Carbon\Carbon::parse($date_reservation . " " . $hours_reservation)->toDateTimeString();

        $turn           = TurnsHelper::TypeTurnWithHourForHour($date_reservation, $hours_reservation, $this->microsite_id);
        $duration       = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $this->req->covers)->first();
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
        $reservation->datetime_output = DateTimesHelper::AddTime($reservation->datetime_input, $reservation->hours_duration);

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
        $reservation->res_source_type_id        = self::_ID_SOURCE_RESERVATION_HOSTESS;
        $reservation->res_reservation_status_id = self::_ID_STATUS_RESERVATION_RESERVED;
        $reservation->status_released           = 0;
        $reservation->wait_list                 = 1;
        $reservation->num_guest                 = $this->req->covers;
        $reservation->note                      = $this->req->note;
        $reservation->phone                     = $phone;
        $reservation->email                     = $email;
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;

        $datetime_reservation = $reservation->date_reservation . " " . $reservation->hours_reservation;
        $datetime_reservation = DateTimesHelper::DiffTime($reservation->date_add, $reservation->quote);
        $datetime_reservation = DateTimesHelper::AddTime($datetime_reservation, $this->req->quote);
        $date                 = \Carbon\Carbon::parse($datetime_reservation);

        $reservation->hours_reservation = $date->toTimeString();
        $reservation->quote             = $this->req->quote;

        $turn           = TurnsHelper::TypeTurnWithHourForHour($reservation->date_reservation, $reservation->hours_reservation, $this->microsite_id);
        $duration       = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $this->req->covers)->first();
        $hours_duration = $duration ? $duration->time : "01:30:00";

        $reservation->hours_duration  = $hours_duration;
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

    //Eliminamos la mesa de la reservacion
    public function deleteTable(int $idTable, int $idReserva)
    {
        DB::table('res_table_reservation')->where('res_table_id', $idTable)->where('res_reservation_id', $idReserva)->delete();
    }

    public function addTable(int $idReserva, array $tables)
    {
        foreach ($tables as $key => $table) {

            $tableReserva                     = new res_table_reservation();
            $tableReserva->res_table_id       = $table;
            $tableReserva->res_reservation_id = $idReserva;
            $tableReserva->num_people         = 0;

            $tableReserva->save();
        }

    }
}
