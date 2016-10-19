<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use Carbon\Carbon;
use DB;

class TableReservationService extends Service
{
    private $guest;

    public function find_guest()
    {
        $this->guest = res_guest::find($this->req->guest_id);
    }

    public function create_guest()
    {
        $guest = new res_guest();
        $guest->first_name = $this->req->guest["first_name"];
        if (isset($this->req->guest["last_name"])) $guest->last_name = $this->req->guest["last_name"];
        $guest->user_add = $this->req->_bs_user_id;
        $guest->ms_microsite_id = $this->microsite_id;

        $guest->save();

        $this->guest = $guest;
    }

    public function create_guest_email()
    {
        $guest_email = new res_guest_email();
        $guest_email->email = $this->req->guest["email"];

        $this->guest->emails()->save($guest_email);
    }

    public function create_guest_phone()
    {
        $guest_phone = new res_guest_phone();
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

        $reservation->res_guest_id = $guest_id;
        $reservation->res_reservation_status_id = $this->req->status_id;
        $reservation->status_released = 0;
        $reservation->num_guest = $this->req->covers;
        $reservation->date_reservation = $this->req->date;
        $reservation->hours_reservation = $this->req->hour;
        $reservation->hours_duration = $this->req->duration;
        $reservation->res_server_id = $this->req->server_id;
        $reservation->note = $this->req->note;
        $reservation->phone = $phone;
        $reservation->email = $email;
        $reservation->user_add = $this->req->_bs_user_id;
        $reservation->ms_microsite_id = $this->microsite_id;

        $reservation->save();

        $tables =array();
        foreach ($this->req->tables as $key => $value) {
            $tables[$value] =  array("num_people" => 0);
        }

        if ($create_or_update == "create") {
            $reservation->tables()->attach($tables);
            $reservation->tags()->attach($this->req->tags);
        } else if ($create_or_update == "update") {
            $reservation->tables()->sync($tables);
            $reservation->tags()->sync($this->req->tags);
        }

        return $reservation;
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
                 "res_server_id"
        );

        return res_reservation::select($get)
            ->with(["tables" => function($query) {
                    return $query->select("res_table.id");
                }, "guest" => function($query) {
                    return $query->select("id", "first_name", "last_name")->with("emails", "phones");
                }, "tags" => function($query) {
                    return $query->select("id");
                }])
            ->where("ms_microsite_id", $this->microsite_id)->find($this->reservation);
    }

    public function update()
    {
        $reservation = res_reservation::find($this->reservation);
        return $this->save_reservation($reservation, "update");
    }

    public function cancel()
    {
        return res_reservation::where("id", $this->reservation)->update(["res_reservation_status_id" => 12]);
    }

    public function quickEdit()
    {
        return res_reservation::where("id", $this->reservation)
                    ->update([
                        "res_reservation_status_id" => $this->req->status_id,
                        "num_guest" => $this->req->covers,
                        "res_server_id" => $this->req->server_id,
                        "note" => $this->req->note,
                    ]);
    }

    public function quickCreate()
    {
        $num_guest = (int)$this->req->covers["men"] +  (int)$this->req->covers["women"] +  (int)$this->req->covers["children"];
        $reservation = new res_reservation();
        $reservation->res_reservation_status_id = 14;
        $reservation->status_released = 0;
        $reservation->num_guest = $num_guest;
        $reservation->num_people_1 = $this->req->covers["men"];
        $reservation->num_people_2 = $this->req->covers["women"];
        $reservation->num_people_3 = $this->req->covers["children"];
        $reservation->date_reservation = $this->req->date;
        $reservation->hours_reservation = $this->req->hour;
        $reservation->hours_duration = "01:30:00";
        $reservation->datetime_input = Carbon::now()->setTimezone($this->req->timezone)->toDateTimeString();
        $reservation->user_add = $this->req->_bs_user_id;
        $reservation->ms_microsite_id = $this->microsite_id;

        $reservation->save();

        $reservation->tables()->attach($this->req->table_id, ["num_people" => $num_guest]);

        return $reservation;
    }

    public function sit()
    {
        $reservation = res_reservation::withCount(['tables' => function ($query) {
            $query->where('res_table_id', $this->req->table_id);
        }])->where("id", $this->reservation)->first();

        if ($reservation != null){
            $reservation->res_reservation_status_id = 14;
            if ($reservation->datetime_input == null) {
                $reservation->datetime_input = Carbon::now()->setTimezone($this->req->timezone)->toDateTimeString();
            }
            $reservation->save();

            if ($reservation->tables_count == 0) {
                $reservation->tables()->sync([$this->req->table_id => ["num_people" => 0]]);
            }
        }

        return $reservation;
    }
}
