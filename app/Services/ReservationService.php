<?php

namespace App\Services;

use App\res_guest;
use App\res_reservation;
use App\res_reservation_status;
use App\res_source_type;
use App\Services\Helpers\CalendarHelper;
use App\Services\Helpers\TurnsHelper;
use App\Services\TableReservationService;
use DB;
use Exception;
use Illuminate\Http\Request;

class ReservationService
{

    protected $_ZoneTableService;
    protected $_TurnsHelper;
    protected $_TableReservationService;

    public function __construct(GuestService $GuestService, Request $request)
    {
        $this->_GuestService            = $GuestService;
        $this->_TurnsHelper             = new TurnsHelper();
        $this->_TableReservationService = TableReservationService::make($request);
    }

    public function get(int $microsite_id, int $reservation_id)
    {
        $rows = res_reservation::where('ms_microsite_id', $microsite_id)
            ->where('id', $reservation_id)->with(["tables" => function ($query) {
            return $query->select("res_table.id", "res_zone_id", "name");
        }, "guest", "server", "source", "status", "turn.typeTurn", "tags", "guestList"])->first();

        return $rows;
    }

    public function getList(int $microsite_id, string $start_date, string $end_date = null, string $name = null, string $email = null, string $phone = null, array $statusIds = [], array $sourceIds = [], array $typeTurnIds = [], array $zoneIds = [], string $sort = null, int $pagesize = 100)
    {
//        $start_date = CalendarHelper::realDate($microsite_id);

        $end_date = (strcmp($end_date, $start_date) > 0) ? $end_date : $start_date;

        $reservations = res_reservation::with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "status", "server", "source", "turn.typeTurn", "tags", "guestList", "guest", "guest.emails", "guest.phones"]);

        $reservations = !(isset($name) && strlen($name) > 0) ? $reservations : $reservations->whereHas('turn.typeTurn', function ($query) use ($name) {
            $query->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%$name%");
        });

        $reservations = (count(@$typeTurnIds) == 0) ? $reservations : $reservations->whereHas('turn', function ($query) use ($typeTurnIds) {
            $query->whereIn('res_type_turn_id', $typeTurnIds);
        });

        $reservations = (count(@$zoneIds) == 0) ? $reservations : $reservations->whereHas('tables', function ($query) use ($zoneIds) {
            $query->whereIn('res_zone_id', $zoneIds);
        });

        $reservations = (count(@$nostatusIds) == 0) ? $reservations : $reservations->where('res_reservation_status_id', '<>', $nostatusIds);
        $reservations = (count(@$sourceIds) == 0) ? $reservations : $reservations->where('res_source_type_id', $sourceIds);
        $reservations = !(isset($email) && strlen($email) > 0) ? $reservations : $reservations->where('email', 'LIKE', "%$email%");

        $reservations = $reservations->whereRaw("date_reservation BETWEEN ? AND ?", array($start_date, $end_date));
        $reservations = $reservations->where("ms_microsite_id", $microsite_id);
//        return $reservations->get();
        return $reservations->paginate($pagesize);
    }

    public function getBook(int $microsite_id, string $start_date = null, string $end_date = null, string $filters)
    {
        $start_date = CalendarHelper::realDate($microsite_id);
        $end_date   = (strcmp($end_date, $start_date) > 0) ? $end_date : $start_date;

        $reservations = res_reservation::select("res.*")->with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList"])->from("res_reservation as res");

        $reservations = $reservations->whereRaw("res.date_reservation BETWEEN ? AND ?", array($start_date, $end_date));

        return $reservations->get();
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
     * */
    public function patch(array $data, int $microsite_id)
    {
        // $reservation = new res_reservation();

        $id = $data['id'];

        $reservation = res_reservation::where('id', $id)->where('ms_microsite_id', $microsite_id)->first();

        if (isset($data["res_reservation_status_id"])) {
            $reservation->res_reservation_status_id = $data["res_reservation_status_id"];
        }

        if (isset($data["num_people_1"])) {
            $reservation->num_people_1 = $data["num_people_1"];
        }

        if (isset($data["num_people_2"])) {
            $reservation->num_people_2 = $data["num_people_2"];
        }

        if (isset($data["num_people_3"])) {
            $reservation->num_people_3 = $data["num_people_3"];
        }

        if (isset($data["consume"])) {
            $reservation->consume = $data["consume"];
        }

        $reservation->save();

        $res = res_reservation::withRelations()->where('id', $id)->where('ms_microsite_id', $microsite_id)->first();

        return $res;
    }

    //Actualizamos la reservacion desde el grid
    public function updateByGrid(array $params, int $microsite_id)
    {
        $reservation                    = res_reservation::where('id', $params['reservation']['id'])->first();
        $reservation->hours_reservation = $params['reservation']['hours_reservation'];
        $reservation->save();

        foreach ($params['tables_deleted'] as $key => $table) {
            $this->_TableReservationService->deleteTable($table, $params['reservation']['id']);
        }

        $this->_TableReservationService->addTable($params['reservation']['id'], $params['tables_add']);

        $res = res_reservation::withRelations()->where('id', $params['reservation']['id'])->where('ms_microsite_id', $microsite_id)->first();

        return $res;

    }

}
