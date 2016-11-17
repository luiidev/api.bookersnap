<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityInfoRequest;
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
        $next_day     = $request->next_day;
        $num_guests   = $request->num_guests;
        $zone_id      = $request->zone_id;

        if (isset($zone_id)) {
            return $this->TryCatch(function () use ($microsite_id, $date, $hour, $next_day, $num_guests, $zone_id, $timezone) {
                $availability = $this->service->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
                return $this->CreateJsonResponse(true, 200, "", $availability);
            });
        } else {
            return $this->TryCatch(function () use ($microsite_id, $date, $hour, $next_day, $num_guests, $zone_id, $timezone) {
                $availability = $this->service->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $timezone);
                return $this->CreateJsonResponse(true, 200, "", $availability);
            });
        }

        // //TEST Disponibilidad
        // //
        // $table              = new TimeForTable();
        // $availabilityTables = $this->service->searchTablesReservation($date, $microsite_id, $zone_id);
        // $indexHour          = $table->timeToIndex($hour);
        // $eventId            = 1;
        // return $this->TryCatch(function () use ($microsite_id, $date, $hour, $num_guests, $zone_id, $indexHour, $timezone, $availabilityTables, $eventId) {
        //     $availability = $this->service->getAvailabilityBasic($microsite_id, $date, $hour, $num_guests, $zone_id, $indexHour, $timezone, $availabilityTables, $eventId);
        //     return $this->CreateJsonResponse(true, 200, "", $availability);
        // });

    }

    public function getZones(AvailabilityInfoRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $timezone     = $request->timezone;
        return $this->TryCatch(function () use ($microsite_id, $date, $timezone) {
            $zones = $this->service->searchZones($microsite_id, $date);
            return $this->CreateJsonResponse(true, 200, "", $zones);
        });
    }

    public function getHours(AvailabilityInfoRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $zone_id      = $request->zone_id;
        $timezone     = $request->timezone;

        return $this->TryCatch(function () use ($microsite_id, $date, $zone_id, $timezone) {
            $hours = $this->service->getHours($microsite_id, $date, $zone_id, $timezone);
            return $this->CreateJsonResponse(true, 200, "", $hours);
        });
    }

    public function getEvents(AvailabilityInfoRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $zone_id      = $request->zone_id;
        $hour         = $request->hour;
        $next_day     = $request->next_day;
        $timezone     = $request->timezone;
        return $this->TryCatch(function () use ($microsite_id, $date, $hour, $zone_id, $timezone, $next_day) {
            $events = $this->service->getEvents($microsite_id, $date, $hour, $timezone, $next_day, $zone_id);
            return $this->CreateJsonResponse(true, 200, "", $events);
        });
    }
}
