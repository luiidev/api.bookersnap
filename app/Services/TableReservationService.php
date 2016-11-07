<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use App\res_turn_time;
use App\Services\Helpers\TurnsHelper;
use Carbon\Carbon;

class TableReservationService extends Service
{
    private $guest;

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

    private function save_reservation(res_reservation $reservation, $create_or_update)
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

        $type_turn = TurnsHelper::TypeTurnForHour($this->req->date, $this->req->hour, $this->microsite_id);

        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = 1;
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
        $reservation->res_type_turn_id          = $type_turn;

        $reservation->save();

        $tables = array();
        foreach ($this->req->tables as $key => $value) {
            $tables[$value] = array("num_people" => 0);
        }

        if ($create_or_update == "create") {
            $reservation->tables()->attach($tables);
            $reservation->tags()->attach($this->req->tags);
        } else if ($create_or_update == "update") {
            $reservation->tables()->sync($tables);
            $reservation->tags()->sync($this->req->tags);
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

        return res_reservation::select($get)
            ->with(["tables" => function ($query) {
                return $query->select("res_table.id");
            }, "guest" => function ($query) {
                return $query->select("id", "first_name", "last_name")->with("emails", "phones");
            }, "tags" => function ($query) {
                return $query->select("id");
            }])
            ->where("ms_microsite_id", $this->microsite_id)->find($this->reservation);
    }

    public function update()
    {
        $reservation = res_reservation::find($this->reservation);
        $data =  $this->save_reservation($reservation, "update");

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
        res_reservation::where("id", $this->reservation)
            ->update([
                "res_reservation_status_id" => $this->req->status_id,
                "num_guest"                 => $this->req->covers,
                "res_server_id"             => $this->req->server_id,
                "note"                      => $this->req->note,
                "num_people_1"              => $this->req->guests["men"],
                "num_people_2"              => $this->req->guests["women"],
                "num_people_3"              => $this->req->guests["children"],
            ]);

        $data = res_reservation::withRelations()->find($this->reservation);

        return array($data);
    }

    public function quickCreate()
    {
        $num_guest = (int) $this->req->guests["men"] + (int) $this->req->guests["women"] + (int) $this->req->guests["children"];

        $turn     = TurnsHelper::TypeTurnWithHourForHour($this->req->date, $this->req->hour, $this->microsite_id);
        $duration = res_turn_time::where("res_turn_id", $turn->turn_id)->where("num_guests", $num_guest)->first();

        $reservation                            = new res_reservation();
        $reservation->res_source_type_id        = 1;
        $reservation->res_reservation_status_id = 4;
        $reservation->status_released           = 0;
        $reservation->num_guest                 = $num_guest;
        $reservation->num_people_1              = $this->req->guests["men"];
        $reservation->num_people_2              = $this->req->guests["women"];
        $reservation->num_people_3              = $this->req->guests["children"];
        $reservation->date_reservation          = $this->req->date;
        $reservation->hours_reservation         = $turn->hour;
        $reservation->hours_duration            = $duration ? $duration->time : "01:30:00";
        $reservation->datetime_input            = Carbon::now()->setTimezone($this->req->timezone)->toDateTimeString();
        $reservation->user_add                  = $this->req->_bs_user_id;
        $reservation->ms_microsite_id           = $this->microsite_id;
        $reservation->res_type_turn_id          = $turn->type_turn_id;

        $reservation->save();

        $reservation->tables()->attach($this->req->table_id, ["num_people" => $num_guest]);

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }

    public function sit()
    {
        $now = Carbon::now()->setTimezone($this->req->timezone);

        $reservation = res_reservation::withCount(['tables' => function ($query) {
            $query->where('res_table_id', $this->req->table_id);
        }])->where("id", $this->reservation)->first();

        if ($reservation !== null) {

            // Actualizar reservacion
            $reservation->res_reservation_status_id = 4;
            if ($reservation->datetime_input === null) {
                $reservation->datetime_input = $now->toDateTimeString();
            }
            $reservation->save();

            if ($reservation->tables_count === 0) {
                $reservation->tables()->sync([$this->req->table_id => ["num_people" => 0]]);
            }
            // end

            // Actualizar otras reservaciones que estasn ocupando la mesa
            $others_reservation = res_reservation::withCount(['tables' => function ($query) {
                $query->where('res_table_id', $this->req->table_id);
            }])->where("id", "<>", $this->reservation)
                ->where("date_reservation", $now->toDateString())
                ->where("res_reservation_status_id", "=", 4)
                ->where("ms_microsite_id", $this->microsite_id)
                ->get();

            $filtered = $others_reservation->filter(function ($item) {
                return $item['tables_count'] > 0;
            });

            if ($others_reservation->count()) {
                res_reservation::whereIn("id", $filtered->pluck("id"))
                    ->update([
                        "res_reservation_status_id" => 5,
                        "datetime_output" =>  $now->toDateTimeString()
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

        $reservation                            = new res_reservation();
        $reservation->res_guest_id              = $guest_id;
        $reservation->res_source_type_id        = 1;
        $reservation->res_reservation_status_id = 1;
        $reservation->status_released           = 0;
        $reservation->wait_list                 = 1;
        $reservation->date_reservation          = Carbon::now()->setTimezone($this->req->timezone)->toDateTimeString();
        $reservation->num_guest                 = $this->req->covers;
        $reservation->hours_reservation         = Carbon::now()->setTimezone($this->req->timezone)->toTimeString();
        $reservation->hours_duration            = "00:00:00";
        $reservation->quote                     = $this->req->quote;

        $reservation->note            = $this->req->note;
        $reservation->phone           = $phone;
        $reservation->email           = $email;
        $reservation->user_add        = $this->req->_bs_user_id;
        $reservation->ms_microsite_id = $this->microsite_id;

        $reservation->save();

        $data = res_reservation::withRelations()->find($reservation->id);

        return $data;
    }
}
