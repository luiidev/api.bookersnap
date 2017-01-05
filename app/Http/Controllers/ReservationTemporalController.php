<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationTemporalRequest;
use App\Services\AvailabilityService;
use App\Services\ConfigurationService;
use App\Services\FormService;
use App\Services\ReservationTemporalService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\Helpers\CalendarHelper;

class ReservationTemporalController extends Controller
{
    private $service;
    private $availabilityService;
    private $configurationService;

    public function __construct(ReservationTemporalService $ReservationTemporalService, AvailabilityService $AvailabilityService, ConfigurationService $ConfigurationService, FormService  $FormService)
    {
        $this->service              = $ReservationTemporalService;
        $this->availabilityService  = $AvailabilityService;
        $this->configurationService = $ConfigurationService;
        $this->formService = $FormService;
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
        // return $request->header("token");
        //inyectar evento
        // $request->request->set('ev_event_id', 1);
        
        return $this->TryCatch(function () use ($request) {
            
//             $token        = request()->cookie('token', request()->cookie('laravel_session'));
            $token        = request()->header("token");
            $ev_event_id = $request->ev_event_id;

            $user_id      = $request->input("_bs_user_id");
            $microsite_id = $request->route('microsite_id');
//            $token        = $request->token;          
            $hour         = $request->hour;
            $date         = $request->date;
            $num_guests   = $request->num_guests;
            $zone_id      = $request->zone_id;
//            $next_day     = $request->next_day;
            $timezone     = $request->timezone;
            
            $reservationTime = CalendarHelper::getDatetimeCalendar($microsite_id, $date, $hour);
            if(!$reservationTime){
                abort(500, "Este horario no existe");
            }
            $realDate = \Carbon\Carbon::parse($reservationTime);
            $next_day = (strcmp($realDate->toDateString(), $date))?1:0;
            
            $configuration = $this->configurationService->getConfiguration($microsite_id);
            $configuration->max_people;
            if ($configuration->max_people < $num_guests) {
                abort(501, "La configuracion del sitio no soporta la esa cantidad de usuario");
            }
            
            $reservationTemporal = $this->service->createReservationTemporal($user_id, $microsite_id, $hour, $date, $num_guests, $zone_id, $timezone, $ev_event_id, $token, $next_day, $num_guests);
            return $this->CreateJsonResponse(true, 200, "", $reservationTemporal);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($lang, $microsite_id, $token)
    {
        
        return $this->TryCatch(function () use ($microsite_id, $token) {

            $reservation_temp = $this->service->getTempReservation($token);            
//            if ($reservation_temp["reservation"] === null) {
//                return response("", 500);
//            }
            $forms = $this->formService->getFormsByMicrosite($microsite_id);

            return $this->CreateJsonResponse(true, 200, "", ["reservation" => $reservation_temp["reservation"], "forms" => $forms, "time" => $reservation_temp["time"]]);
        });
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
    public function destroy($lang, $microsite_id, $token)
    {
        return $this->TryCatch(function () use ($microsite_id, $token) {
            $this->service->temporalReserveFinish($token);

            return $this->CreateJsonResponse(true, 200, "");
        });
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function expire(Request $request)
    {       
        return $this->TryCatch(function () use ($request) {
            
            $microsite_id = $request->route("microsite_id");
            $token = $request->header("token");
            $result = $this->service->temporalReserveExpire($microsite_id, $token);

            return $this->CreateJsonResponse(true, 200, "", $result);
        });
    }
}
