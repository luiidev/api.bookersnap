<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use DB;

class TableReservationService extends Service
{
    private $guest;
    private $reservation;

    public function find_guest()
    {
        $this->guest = res_guest::find($this->req->guest_id);
    }

    public function create_guest()
    {
        $guest = new res_guest();
        $guest->first_name = $this->req->guest["first_name"];
        $guest->last_name = $this->req->guest["last_name"];
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

    public function create_reservation()
    {
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

        $reservation = new res_reservation();
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

        // tables attach
        $tables =array();
        foreach ($this->req->tables as $key => $value) {
            $tables[$value] =  array("num_people" => $this->req->covers);
        }
        $reservation->tables()->attach($tables);

        $this->reservation = $reservation;
    }

    public function add_reservation_tags()
    {
        $this->reservation->tags()->attach($this->req->tags);
    }

    public function show($microsite_id, $id)
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
            ->where("ms_microsite_id", $microsite_id)->find($id);
    }
}
