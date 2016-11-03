<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Services\AvailabilityService;

class AvailabilityController extends Controller
{
    private $service;

    public function __construct(AvailabilityService $AvailabilityService)
    {
        $this->service = $AvailabilityService;
    }

    public function basic(AvailabilityRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $hour         = $request->hour;
        $timezone     = $request->timezone;
        $timezone     = 'America/Lima';
        $next_day     = $request->next_day;
        $num_guests   = $request->num_guests;
        $zone_id      = $request->zone_id;

        // testArrayDay
        return $this->TryCatch(function () use ($microsite_id, $date, $hour, $next_day, $num_guests, $zone_id, $timezone) {
            $availability = $this->service->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
            return $this->CreateJsonResponse(true, 200, "", $availability);
        });
        // return $this->TryCatch(function () use ($microsite_id, $date, $hour, $next_day, $num_guests, $zone_id) {
        //     $availability = $this->service->getAvailabilityBasic($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day);
        //     return $this->CreateJsonResponse(true, 200, "", $availability);
        // });
    }
}
