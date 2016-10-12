<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_email;
use App\res_guest_phone;
use App\res_reservation;
use DB;

class TableReservationService
{
    private $lang;
    private $microsite_id;
    private $guest;
    private $reservation;

    public function __construct(string $lang, int $microsite_id)
    {
        $this->lang = $lang;
        $this->microsite_id = $microsite_id;
    }

    public static function make($lang, $microsite_id) {
        return new static((string)$lang, (int)$microsite_id);
    }

    public function find_guest()
    {
        $this->guest = res_guest::find(request("guest_id"));
    }

    public function create_guest()
    {
        $guest = new res_guest();
        $guest->first_name = request("guest.first_name");
        $guest->last_name = request("guest.last_name");
        $guest->user_add = request("_bs_user_id");
        $guest->ms_microsite_id = $this->microsite_id;

        $guest->save();

        $this->guest = $guest;
    }

    public function create_guest_email()
    {
        $guest_email = new res_guest_email();
        $guest_email->email = request("guest.email");

        $this->guest->emails()->save($guest_email);
    }

    public function create_guest_phone()
    {
        $guest_phone = new res_guest_phone();
        $guest_phone->number = request("guest.phone");

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
        $reservation->status_released = request("status_id");
        $reservation->num_people = request("covers");
        $reservation->date_reservation = request("date");
        $reservation->hours_reservation = request("hour");
        $reservation->hours_duration = request("duration");
        // $reservation->res_server_id = request("server_id");
        $reservation->note = request("note");
        $reservation->phone = $phone;
        $reservation->email = $email;
        $reservation->user_add = request("_bs_user_id");
        $reservation->ms_microsite_id = $this->microsite_id;

        $reservation->save();

        // tables attach
        $tables =array();
        foreach (request("tables") as $key => $value) {
            $tables[$value] =  array("num_people" => request("covers"));
        }
        $reservation->tables()->attach($tables);

        $this->reservation = $reservation;
    }

    public function add_reservation_tags()
    {
        $this->reservation->tags()->attach(request("tags"));
    }
}
