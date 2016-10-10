<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\TableReservationRequest;
use App\Services\TableReservationService as Service;
use Illuminate\Http\Request;

class TableReservationController extends Controller
{
    private $service;

    function __construct(Request $request)
    {
        $this->service = Service::make(
                                                        $request->route("lang"),
                                                        $request->route("microsite_id")
                                                     );
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
        return $this->TryCatchDB(function() use ($request) {

            if ($request->has("guest_id")) {
                $this->service->find_guest();
            } else {
                if ($request->has("guest.first_name") || $request->has("guest.last_name")) {
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
