<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityInfoRequest;
use App\Http\Requests\AvailabilityRequest;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use App\Services\Helpers\CalendarHelper;

class AvailabilityController extends Controller
{
    private $service;

    public function __construct(AvailabilityService $AvailabilityService)
    {
        $this->service = $AvailabilityService;
    }

    public function basic(AvailabilityRequest $request)
    {
        return $this->TryCatch(function () use ($request) {
            $microsite_id = $request->route('microsite_id');
            $date         = $request->date;
            $hour         = $request->hour;
            $timezone     = $request->timezone;
            $num_guests   = $request->num_guests;
            $zone_id      = $request->zone_id;
            $event_id     = $request->event_id;
            
            $reservationTime = CalendarHelper::getDatetimeCalendar($microsite_id, $date, $hour);
            if(!$reservationTime){
                abort(500, "Este horario no existe");
            }
            
            $realDate = \Carbon\Carbon::parse($reservationTime);
            $next_day = (strcmp($realDate->toDateString(), $date))?1:0;

            if (isset($zone_id)) {
                $availability = $this->service->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone, $event_id);
                return $this->CreateJsonResponse(true, 200, "", $availability);
            } else {
                $availability = $this->service->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $timezone, $event_id);
                return $this->CreateJsonResponse(true, 200, "", $availability);

            }
        });
    }

    public function getZones(AvailabilityInfoRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $timezone     = $request->timezone;
        return $this->TryCatch(function () use ($microsite_id, $date, $timezone) {
            $zones = $this->service->searchZones($microsite_id, $date, $timezone);
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
    //     return phpinfo();
        $hour = \Carbon\Carbon::now()->toTimeString();
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $zone_id      = $request->zone_id;
        $hour         = $request->hour;
//        $next_day     = $request->next_day;
        $timezone     = $request->timezone;
        return $this->TryCatch(function () use ($microsite_id, $date, $hour, $zone_id, $timezone) {
            
            $reservationTime = CalendarHelper::getDatetimeCalendar($microsite_id, $date, $hour);
            if(!$reservationTime){
                abort(500, "Este horario no existe");
            }
            $realDate = \Carbon\Carbon::parse($reservationTime);
            $next_day = (strcmp($realDate->toDateString(), $date))?1:0;
            
            $this->service->validNextDate($date, $next_day, $timezone);
            $events = $this->service->getEvents($microsite_id, $date, $hour, $timezone, $next_day, $zone_id);
            return $this->CreateJsonResponse(true, 200, "", $events);
        });
    }

    public function getDays(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date_ini     = $request->date_ini;
        $date_fin     = $request->date_fin;
        $timezone     = $request->timezone;

        return $this->TryCatch(function () use ($microsite_id, $date_ini, $date_fin, $timezone) {
            $days = $this->service->getDays($microsite_id, $date_ini, $date_fin, $timezone);
            return $this->CreateJsonResponse(true, 200, "", $days);
        });
    }

    public function getDaysDisabled(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date_ini     = $request->date_ini;
        $date_fin     = $request->date_fin;
        $timezone     = $request->timezone;

        return $this->TryCatch(function () use ($microsite_id, $date_ini, $date_fin, $timezone) {
            $daysDisabled = $this->service->getDaysDisabled($microsite_id, $date_ini, $date_fin, $timezone);
            return $this->CreateJsonResponse(true, 200, "", $daysDisabled);
        });
    }

    public function getPeople(Request $request)
    {
        $microsite_id = $request->route('microsite_id');

        return $this->TryCatch(function () use ($microsite_id) {
            $people = $this->service->getPeople($microsite_id);
            return $this->CreateJsonResponse(true, 200, "", $people);
        });
    }

    public function getFormatAvailability(Request $request)
    {
        return $this->TryCatch(function () use ($request) {
            $microsite_id = $request->route('microsite_id');
            $date = $request->input('date', \Carbon\Carbon::now()->toDateString());
            $people = $this->service->formatAvailability($microsite_id, $date);
            return $this->CreateJsonResponse(true, 200, "", $people);
        });
    }
}
