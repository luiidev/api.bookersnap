<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationTemporalRequest;
use App\Services\ReservationTemporalService;
use Illuminate\Http\Request;

class ReservationTemporalController extends Controller
{
    private $service;

    public function __construct(ReservationTemporalService $ReservationTemporalService)
    {
        $this->service = $ReservationTemporalService;
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

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReservationTemporalRequest $request)
    {
        $request->request->set('ev_event_id', 1);

        $user_id      = $request->input("_bs_user_id");
        $microsite_id = $request->route('microsite_id');
        $hour         = $request->hour;
        $date         = $request->date;
        $num_guest    = $request->num_guest;
        $zone_id      = $request->zone_id;
        $tables_id    = $request->tables_id;
        $ev_event_id  = $request->ev_event_id;

        return $this->TryCatch(function () use ($user_id, $microsite_id, $hour, $date, $num_guest, $zone_id, $tables_id, $ev_event_id) {
            $reservationTemporal = $this->service->createReservationTemporal($user_id, $microsite_id, $hour, $date, $num_guest, $zone_id, $tables_id, $ev_event_id);
            return $this->CreateJsonResponse(true, 200, "", $reservationTemporal);
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
