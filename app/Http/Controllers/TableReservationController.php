<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\TableReservationRequest;
use App\Services\TableReservationService as Service;
use Illuminate\Http\Request;

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
        return $this->TryCatchDB(function() {
            $reservation = $this->service->create_reservation();
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
        $reservation = $this->service->edit();

        return $this->CreateJsonResponse(true, 200, "", $reservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TableReservationRequest $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function() {
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
        return $this->TryCatchDB(function() {
            $confirmation =  $this->service->cancel();
            if ($confirmation) {
                return $this->CreateJsonResponse(true, 200, "La reservacion fue cancelada.");
            } else {
                return $this->CreateJsonResponse(true, 422, null, null, null, null, "No se enontro la reservacion o ya fue cancelada.");
            }
        });
    }
}
