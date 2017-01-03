<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Events\SendMailEvent;
use App\Http\Requests;
use App\Http\Requests\GuestListRequest;
use App\Http\Requests\ReservationFromWebRequest;
use App\Http\Requests\TableReservationRequest;
use App\Services\TableReservationService as Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableReservationController extends Controller
{
    private $service;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param App\Http\Requests\TableReservationRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(TableReservationRequest $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->create_reservation();

            $this->_notification($request->route("microsite_id"), $reservation, "Se creo una nueva reservación", "create", $request->key);

            return $this->CreateJsonResponse(true, 201, "La reservacion fue registrada", $reservation);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByCrypt(Request $request)
    {
        $this->service = Service::make($request);
        $reservation   = $this->service->showByCrypt();

        if ($reservation !== null ) {
            return $this->CreateJsonResponse(true, 200, "", $reservation);
        } else {
            return response("", 404);
        }
    }

    public function cancelReserveWeb(Request $request)
    {
        $this->service = Service::make($request);
        $reservation = $this->service->cancelReserveWeb();
        
        $this->_notification($request->route("microsite_id"), [$reservation], "Se ha cancelado una reservación", "update", @$request->key);

        return $this->CreateJsonResponse(true, 200, "Se ha cancelado una reservación", $reservation);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $this->service = Service::make($request);
        $reservation   = $this->service->edit();

        return $this->CreateJsonResponse(true, 200, "", $reservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  App\Http\Requests\TableReservationRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TableReservationRequest $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->update();

            $this->_notification($request->route("microsite_id"), $reservation, "Se edito una reservación", "update", $request->key);

            return $this->CreateJsonResponse(true, 200, "Se actualizo la reservacion.", $reservation);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function cancel(Request $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->cancel();
            if ($reservation) {
                $this->_notification($request->route("microsite_id"), $reservation, "Se cancelo una reservación", "update", $request->key);

                return $this->CreateJsonResponse(true, 200, "La reservacion fue cancelada.");
            } else {
                return $this->CreateJsonResponse(true, 422, null, null, null, null, "No se enontro la reservacion o ya fue cancelada.");
            }
        });
    }

    public function quickEdit(Request $request)
    {

        $rules = [
            "id"              => "exists:res_reservation",
            "status_id"       => "required|exists:res_reservation_status,id",
            "covers"          => "required|integer|between:1,999",
            "server_id"       => "exists:res_server,id",
            "note"            => "string",
            "guests"          => "required|array",
            "guests.men"      => "required|integer",
            "guests.women"    => "required|integer",
            "guests.children" => "required|integer",
            "tags"            => "array",
            "tags.*"          => "exists:res_tag_r,id",
        ];

        $request["id"] = $request->route("reservation");

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(false, 422, "", $validator->errors(), null, null, "Parametro incorrectos");
        }

        $this->service = Service::make($request);
        return $this->TryCatch(function () use ($request) {
            $reservation = $this->service->quickEdit();

            $this->_notification($request->route("microsite_id"), $reservation, "Actualización mesa rápida", "update", $request->key);

            return $this->CreateJsonResponse(true, 200, "La reservacion fue actualizada.", $reservation);
        });
    }

    public function quickCreate(Request $request)
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $rules = [
            "table_id"        => "required|exists:res_table,id",
            "guests"          => "required|array",
            "guests.men"      => "required|integer",
            "guests.women"    => "required|integer",
            "guests.children" => "required|integer",
        ];

        $request["id"] = $request->route("reservation");

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(false, 422, "", $validator->errors(), null, null, "Parametro incorrectos");
        }

        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {

            $reservation = $this->service->quickCreate();
            $this->_notification($request->route("microsite_id"), $reservation, "Se ha creado nueva reservación rápida", "create", $request->key);
            $reservations = $this->service->releasedReservations($request->route("microsite_id"), $reservation->id, $request->table_id);
            
            if($reservations){
                $this->_notification($request->route("microsite_id"), $reservations, "Se han liberado reservaciónciones", "update", null);
            }
            return $this->CreateJsonResponse(true, 200, "La reservacion fue registrada.", $reservation);
        });
    }

    public function sit(Request $request)
    {
        $rules = [
            "table_id" => "required|exists:res_table,id",
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(false, 422, "", $validator->errors(), null, null, "Parametro incorrectos");
        }

        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservations = $this->service->sit();

            if ($reservations) {
                $this->_notification($request->route("microsite_id"), $reservations, "Actualización de reservación", "update", $request->key);
                return $this->CreateJsonResponse(true, 200, "", $reservations);
            } else {
                return $this->CreateJsonResponse(true, 422, null, null, null, null, "No se enontro la reservacion.");
            }
        });
    }

    public function updateGuestList(GuestListRequest $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservations = $this->service->updateGuestList();

            $this->_notification($request->route("microsite_id"), $reservations, "", "update", $request->key);
            return $this->CreateJsonResponse(true, 200, "", $reservations[0]);
        });
    }

    public function storeFromWeb(ReservationFromWebRequest $request) {
            $this->service = Service::make($request);
            return $this->TryCatchDB(function () use ($request) {
                
                $data = $this->service->storeFromWeb($request->header("token"));                
                $this->_notification($request->route("microsite_id"), $data["reservation"], "", "create", $request->key);
                $this->sendConfirmWebReserveMail($data["reservation"], $data["site"]);
                $this->sendConfirmWebMasterReserveMail($data["reservation"], $data["site"]);

                return $this->CreateJsonResponse(true, 200, "", $data["reserve_key"]);
            });
    }

    public function createWaitList(Request $request)
    {
        $this->service = Service::make($request);

        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->create_waitlist();

            $this->_notification($request->route("microsite_id"), $reservation, "Nueva Lista de espera", "create", $request->key);
            return $this->CreateJsonResponse(true, 201, "La lista de espera fue registrada", $reservation);
        });
    }

    public function updateWaitList(Request $request)
    {
        $this->service = Service::make($request);

        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->update_waitlist();

            $this->_notification($request->route("microsite_id"), $reservation, "Lista de espera actualizada", "update", $request->key);
            return $this->CreateJsonResponse(true, 201, "La lista de espera fue actualizada", $reservation);
        });
    }

    public function deleteWaitList(Request $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->delete_waitlist();

            $this->_notification($request->route("microsite_id"), $reservation, "Lista de espera cancelada", "update", $request->key);
            return $this->CreateJsonResponse(true, 201, "La lista de espera fue cancelada", $reservation);
        });
    }

    private function _notification(Int $microsite_id, $data, String $message, String $action, String $key = null)
    {
        event(new EmitNotification("b-mesas-floor-res",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $data,
                'action'       => $action,
                'key'          => $key
            )
        ));
    }

    private function sendConfirmWebReserveMail($reservation, $site) {
        // Mas info
        // $to = array(
        //     'email' => $reservation->email,
        //     'name'  => $reservation->guest['first_name'].' '.$reservation->guest['last_name'],
        // );

        $to =$reservation->email;

        $body = array(
            "template" => "emails.web_reserve",
            "data" =>  array(
                "reservation" => $reservation,
                "site" => $site
            )
        );

        $config = "web_reserve";

        $from = array(
            "subject" => strtoupper(str_random(5))." Confirmación de reserva ".$site->name
        );

        event(new SendMailEvent($to, $body, $config, $from));
    }

    private function sendConfirmWebMasterReserveMail($reservation, $site) {
        $to =$site->email;

        $body = array(
            "template" => "emails.web_master_reserve",
            "data" =>  array(
                "reservation" => $reservation,
                "site" => $site
            )
        );

        $config = "web_master_reserve";

        $from = array(
            "subject" => strtoupper(str_random(5)).' '.
                                    $reservation->guest['first_name'].' '.
                                    $reservation->guest['last_name'].' hizo una reservación en '.
                                    $site->name.'. '.
                                    $reservation->date_reservation.' '.
                                    date('h:i A', strtotime($reservation->hours_reservation))
        );

        event(new SendMailEvent($to, $body, $config, $from));
    }

}
