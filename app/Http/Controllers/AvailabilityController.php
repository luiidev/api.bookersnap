<?php

namespace App\Http\Controllers;

use App\Http\Requests\AvailabilityRequest;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;

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

    }

    public function getZones(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $timezone     = $request->timezone;
        return $this->TryCatch(function () use ($microsite_id, $date, $timezone) {
            $dateMin = Carbon::yesterday($timezone)->toDateString();
            if (Validator::make(["date" => $date], ["date" => "date_format: Y-m-d|after:$dateMin"])->fails()) {
                abort(406, "La fecha de consulta no es valida YYYY-mm-dd");
            }
            $zones = $this->service->searchZones($microsite_id, $date);
            return $this->CreateJsonResponse(true, 200, "", $zones);
        });
    }

    public function getHours(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $date         = $request->date;
        $zone_id      = $request->zone_id;
        $timezone     = $request->timezone;

        return $this->TryCatch(function () use ($microsite_id, $date, $zone_id, $timezone) {
            $dateMin = Carbon::yesterday($timezone)->toDateString();
            if (Validator::make(["date" => $date], ["date" => "date_format: Y-m-d|after:$dateMin"])->fails()) {
                abort(406, "La fecha de consulta no es valida YYYY-mm-dd");
            }
            $hours = $this->service->getHours($microsite_id, $date, $zone_id, $timezone);
            return $this->CreateJsonResponse(true, 200, "", $hours);
        });
    }
}
