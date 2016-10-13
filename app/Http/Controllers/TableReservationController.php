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
        return $this->TryCatchDB(function() use ($request) {

            if ($request->has("guest_id")) {
                $this->service->find_guest();
            } else {
                if ($request->has("guest.first_name")) {
                    $this->service->create_guest();

                    if ($request->has("guest.email")) {
                        $this->service->create_guest_email();
                    }

                    if ($request->has("guest.phone")) {
                        $this->service->create_guest_phone();
                    }
                }
            }

            $this->service->create_reservation();

            if ($request->has("tags")) {
                $this->service->add_reservation_tags();
            }

            return $this->CreateJsonResponse(true, 201, "La reservacion fue registrada");
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
    public function edit($lang, $microsite_id, $id)
    {
        $this->service = Service::make();
        $reservation = $this->service->show($microsite_id, $id);
        return $this->CreateJsonResponse(true, 200, "", $reservation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
