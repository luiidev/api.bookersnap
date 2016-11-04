<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Requests;
use App\Http\Requests\TableReservationRequest;
use App\Services\ReservationService;
use App\Services\TableReservationService as Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TableReservationController extends Controller
{
    private $service;
    private $_ReservationService;
    public function __construct(ReservationService $ReservationService)
    {
        $this->_ReservationService = $ReservationService;
    }

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

            $this->_notificationReservation($request->route("microsite_id"), $reservation->id, "Se creo una nueva reservación");

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
        return $this->TryCatchDB(function () {
            $reservation = $this->service->update();
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
        return $this->TryCatchDB(function () {
            $confirmation = $this->service->cancel();
            if ($confirmation) {
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
        ];

        $request["id"] = $request->route("reservation");

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(false, 422, "", $validator->errors(), null, null, "Parametro incorrectos");
        }

        $this->service = Service::make($request);
        return $this->TryCatch(function () use ($request) {
            $this->service->quickEdit();

            $this->_notificationReservation($request->route("microsite_id"), $request->route("reservation"), "Actualización mesa rápida");

            return $this->CreateJsonResponse(true, 200, "La reservacion fue actualizada.");
        });
    }

    public function quickCreate(Request $request)
    {
        $yesterday = Carbon::yesterday()->setTimezone($request->timezone)->toDateString();
        $rules     = [
            "date"            => "required|date|after:$yesterday",
            "hour"            => "required",
            "table_id"        => "required|exists:res_table,id",
            "guests"          => "required|array",
                "guests.men"      => "required|integer",
                "guests.women"    => "required|integer",
                "guests.children" => "required|integer",
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(false, 422, "", $validator->errors(), null, null, "Parametro incorrectos");
        }

        $this->service = Service::make($request);
        return $this->TryCatchDB(function () use ($request) {

            $reservation = $this->service->quickCreate();

            $this->_notificationReservation($request->route("microsite_id"), $reservation->id, "Se ha creado nueva reservación rápida");

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
                $this->_notification($request->route("microsite_id"), $reservations, "Actualización de reservación");
                return $this->CreateJsonResponse(true, 200, "", $reservations);
            } else {
                return $this->CreateJsonResponse(true, 422, null, null, null, null, "No se enontro la reservacion.");
            }
        });
    }

    public function createWaitList(Request $request)
    {
        $this->service = Service::make($request);

        return $this->TryCatchDB(function () use ($request) {
            $reservation = $this->service->create_waitlist();

            $this->_notificationReservation($request->route("microsite_id"), $reservation->id, "Hay una actualización de reservación (Lista de espera)");
            return $this->CreateJsonResponse(true, 201, "La lista de espera fue registrada", $reservation);
        });
    }

    private function _notificationReservation(Int $microsite_id, Int $reservation_id, String $message)
    {
        $reservationData = $this->_ReservationService->get($microsite_id, $reservation_id);

        event(new EmitNotification("b-mesas-floor-upd-res",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $reservationData,
            )
        ));
    }

    private function _notification(Int $microsite_id, $data, String $message)
    {
        event(new EmitNotification("b-mesas-floor-upd-res",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $data,
            )
        ));
    }


}
