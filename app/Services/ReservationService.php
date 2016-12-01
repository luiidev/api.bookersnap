<?php

namespace App\Services;

use App\res_guest;
use App\res_reservation;
use App\res_reservation_status;
use App\res_source_type;
use DB;
use Exception;

class ReservationService
{

    protected $_ZoneTableService;

    public function __construct(GuestService $GuestService)
    {
        $this->_GuestService = $GuestService;
    }

    public function get(int $microsite_id, int $reservation_id)
    {
        $rows = res_reservation::where('ms_microsite_id', $microsite_id)
            ->where('id', $reservation_id)->with(["tables" => function ($query) {
            return $query->select("res_table.id", "res_zone_id", "name");
        }, "guest", "server", "source", "status", "turn.typeTurn", "tags", "guestList"])->first();

        return $rows;
    }

    public function getList(int $microsite_id, string $start_date, string $end_date)
    {
        /*$reservations = res_reservation::where('ms_microsite_id', $microsite_id)
        ->where('date_reservation', $date)->with(["tables" => function ($query) {
        return $query->select("res_table.id", "res_zone_id", "name");
        }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "typeTurn", "tags"])->get();*/

        $reservations = res_reservation::where('ms_microsite_id', $microsite_id)->with(["tables" => function ($query) {
            return $query->select("res_table.id", "res_zone_id", "name");

        }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList"]);

        $reservations = $reservations->whereBetween("date_reservation", array($start_date, $end_date));

        return $reservations->get()->toArray();
    }

    public function create(array $data, int $microsite_id, int $user_id)
    {
        DB::beginTransaction();
        try {
            $reservation                            = new res_reservation();
            $reservation->email                     = $data["email"];
            $reservation->ms_microsite_id           = $microsite_id;
            $reservation->phone                     = $data["phone"];
            $reservation->date_reservation          = date("Y-m-d", strtotime($data["date_reservation"]));
            $reservation->hours_reservation         = date("Y-m-d", strtotime($data["hours_reservation"]));
            $reservation->hours_duration            = date("h:i:s", strtotime($data["hours_duration"]));
            $reservation->num_people                = $data["num_people"];
            $reservation->note                      = $data["note"];
            $reservation->res_reservation_status_id = 1;
            $reservation->res_source_type_id        = 1;
            $reservation->user_add                  = $user_id;
            $reservation->date_add                  = \Carbon\Carbon::now();
            $reservation->date_upd                  = $reservation->date_add;

            $guest_id = res_guest::find($data["res_guest_id"]);
            if ($guest_id == null) {
                $data_guest                = ["first_name" => $data["first_name"], "last_name" => $data['last_name']];
                $res_guest_id              = $this->createGuest($data_guest, $microsite_id, $user_id);
                $reservation->res_guest_id = $res_guest_id;

            } else {
                $reservation->res_guest_id = $data["res_guest_id"];
            }

            if (!$reservation->save()) {
                throw new Exception('messages.reservation_save_error');
            }
            //dd($reservation);
            DB::Commit();
            $response["mensaje"] = "messages.reservation_create_success";
            $response["estado"]  = true;

        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();
        }
        return (object) $response;
    }

    public function update(array $data, int $microsite_id, int $reservation_id, int $user_id)
    {
        DB::BeginTransaction();
        try {
            $reservation = res_reservation::where('id', $reservation_id)->where('ms_microsite_id', $microsite_id)->first();
            if ($reservation == null) {
                throw new Exception('messages.block_not_exist_turn');
            }

            $reservation->email                     = $data["email"];
            $reservation->ms_microsite_id           = $microsite_id;
            $reservation->phone                     = $data["phone"];
            $reservation->date_reservation          = date("Y-m-d", strtotime($data["date_reservation"]));
            $reservation->hours_reservation         = date("Y-m-d", strtotime($data["hours_reservation"]));
            $reservation->hours_duration            = date("h:i:s", strtotime($data["hours_duration"]));
            $reservation->num_people                = $data["num_people"];
            $reservation->note                      = $data["note"];
            $reservation->res_reservation_status_id = 1;
            $reservation->user_add                  = $user_id;
            $reservation->date_add                  = \Carbon\Carbon::now();
            $reservation->date_upd                  = $reservation->date_add;

            $guest_id = res_guest::find($data["res_guest_id"]);
            if ($guest_id == null) {
                $data_guest                = ["first_name" => $data["first_name"], "last_name" => $data['last_name']];
                $res_guest_id              = $this->createGuest($data_guest, $microsite_id, $user_id);
                $reservation->res_guest_id = $res_guest_id;

            } else {
                $reservation->res_guest_id = $data["res_guest_id"];
            }

            if (!$reservation->save()) {
                throw new Exception('messages.reservation_error_update');
            }

            DB::commit();
            $response["mensaje"] = "messages.reservation_update_success";
            $response["estado"]  = true;

        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();
        }
        return (object) $response;
    }

    public function delete(int $microsite_id, int $reservation_id)
    {
        DB::BeginTransaction();
        try {
            $reservation = new res_reservation();

            $reservation->where('id', $reservation_id)->where('ms_microsite_id', $microsite_id)->update(["res_reservation_status_id" => 2]);
            DB::Commit();
            $response["mensaje"] = "messages.reservation_update_success";
            $response["estado"]  = true;
            //return true;
        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();
            //abort(500, $e->getMessage());
        }
        return (object) $response;
    }

    public function createGuest(array $data, int $microsite_id, int $user_id)
    {
        try {
            $guest                  = new res_guest();
            $guest->first_name      = $data['first_name'];
            $guest->last_name       = empty($data['last_name']) ? null : $data['last_name'];
            $guest->ms_microsite_id = $microsite_id;
            $guest->user_add        = $user_id;
            $guest->date_add        = \Carbon\Carbon::now();

            $guest->save();
            return $guest->id;

        } catch (\Exception $e) {

            abort(500, "Ocurrio un error interno");
        }
    }

    /**
     * Retorna todos los tipos de estado que puede tener una reservacion
     * @return Collection App\res_reservation_status
     */
    public function listStatus()
    {
        return res_reservation_status::where("status", 1)->get(array("id", "name", "color"));
    }

    /**
     * Retorna todos los tipos de origen de una reservacion
     * @return Collection App\res_source_type
     */
    public function listSourceType()
    {
        return res_source_type::where("status", 1)->get(array("id", "name", "description"));
    }
    /**
    Actualizamos algunos datos de la reserva
     **/
    public function patch(array $data, int $microsite_id)
    {
        $reservation = new res_reservation();

        $id = $data['id'];
        unset($data['id']);
        unset($data['timezone']);
        unset($data['key']);
        unset($data['_bs_user_id']);

        $reservation->where('id', $id)->where('ms_microsite_id', $microsite_id)->update($data);

        $res = res_reservation::withRelations()->where('id', $id)->where('ms_microsite_id', $microsite_id)->first();

        return $res;
    }

}
