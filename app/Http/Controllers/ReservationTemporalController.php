<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationTemporalRequest;
use App\Services\AvailabilityService;
use App\Services\ConfigurationService;
use App\Services\ReservationTemporalService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationTemporalController extends Controller
{
    private $service;
    private $availabilityService;
    private $configurationService;

    public function __construct(ReservationTemporalService $ReservationTemporalService, AvailabilityService $AvailabilityService, ConfigurationService $ConfigurationService)
    {
        $this->service              = $ReservationTemporalService;
        $this->availabilityService  = $AvailabilityService;
        $this->configurationService = $ConfigurationService;
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
        //inyectar evento
        // $request->request->set('ev_event_id', 1);

        return $this->TryCatch(function () use ($request) {
            $ev_event_id = $request->ev_event_id;

            $user_id      = $request->input("_bs_user_id");
            $microsite_id = $request->route('microsite_id');
            $token        = $request->token;
            $hour         = $request->hour;
            $date         = $request->date;
            $num_guests   = $request->num_guests;
            $zone_id      = $request->zone_id;
            $next_day     = $request->next_day;
            $timezone     = $request->timezone;
            try {
                $configuration = $this->configurationService->getConfiguration($microsite_id);
                $configuration->max_people;
                if ($configuration->max_people < $num_guests) {
                    abort(501, "La configuracion del sitio no soporta la esa cantidad de usuario");
                }
                if (isset($zone_id)) {
                    $availability = $this->availabilityService->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
                } else {
                    $availability = $this->availabilityService->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $timezone);
                }
            } catch (\Exception $e) {
                $dateExpire = Carbon::now($timezone)->subMinutes($this->service->getTimeTolerance())->toDateTimeString();
                $this->service->deleteTemporal($dateExpire, $token);
                abort(500, $e->getMessage());
            }
            // return $availability;
            $reservationTemporal = $this->service->createReservationTemporal($user_id, $microsite_id, $hour, $date, $num_guests, $zone_id, $timezone, $availability, $ev_event_id, $token, $next_day, $num_guests);
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
