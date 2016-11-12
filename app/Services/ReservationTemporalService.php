<?php

namespace App\Services;

use App\Entities\res_table_reservation_temp;
use Carbon\Carbon;

class ReservationTemporalService
{
    // private $hash          = "test";
    private $timeTolerance = 10;
    private $timezone;
    private $tokenAuth;
    private $configurationService;
    private $availabilityService;
    public function __construct(ConfigurationService $ConfigurationService, AvailabilityService $AvailabilityService)
    {
        $this->configurationService = $ConfigurationService;
        $this->availabilityService  = $AvailabilityService;
    }

    public function createReservationTemporal(int $user_id, int $microsite_id, string $hour, string $date, int $num_guest, $zone_id, string $timezone, $reservation, $ev_event_id, $tokenAuth, $next_day, $num_guests)
    {
        $this->timezone     = $timezone;
        $this->tokenAuth    = $tokenAuth;
        $dateExpirePrevious = Carbon::now($this->timezone)->subMinutes($this->timeTolerance)->toDateTimeString();
        $exists             = res_table_reservation_temp::where('token', $this->tokenAuth)->where('expire', '>=', $dateExpirePrevious)->get();
        if ($exists->isEmpty()) {
            if (!isset($zone_id)) {
                $zone_id = $reservation[2]['zone_id'];
            }
            $tables_id = $reservation[2]['tables_id'];
            // $ev_event_id = $reservation[2]['ev_event_id'];

            $update              = false;
            $reservationTemporal = [
                'hour'            => $hour,
                'date'            => $date,
                'num_guest'       => $num_guest,
                'user_id'         => $user_id,
                'ev_event_id'     => $ev_event_id,
                'ms_microsite_id' => $microsite_id];
            return $this->createUpdateTemp($zone_id, $tables_id, $reservationTemporal, $update, $date, $hour, $num_guest, $next_day);
        } else {

            $reservationTemporal = $exists->first();
            if ($reservationTemporal->hour == $hour && $reservationTemporal->date == $date && $reservationTemporal->num_guest == $num_guest && $reservationTemporal->zone_id == $zone_id) {
                return $reservationTemporal;
            } else {
                if (isset($zone_id)) {
                    $availability = $this->availabilityService->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $this->timezone);
                } else {
                    $availability = $this->availabilityService->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $this->timezone);
                    $zone_id      = $reservation[2]['zone_id'];
                }
                $tables_id = $availability[2]['tables_id'];
                // $ev_event_id = $availability[2]['ev_event_id'];

                $update                               = true;
                $reservationTemporal->hour            = $hour;
                $reservationTemporal->date            = $date;
                $reservationTemporal->num_guest       = $num_guest;
                $reservationTemporal->user_id         = $user_id;
                $reservationTemporal->ev_event_id     = $ev_event_id;
                $reservationTemporal->ms_microsite_id = $microsite_id;
                return $this->createUpdateTemp($zone_id, $tables_id, $reservationTemporal, $update, $date, $hour, $num_guest, $next_day);

            }
        }

    }
    public function createUpdateTemp($zone_id, $tables_id, $reservationTemporal, $update, string $date, string $hour, int $num_guest, int $next_day)
    {
        if (isset($zone_id) && isset($tables_id)) {
            // return $configuration = $this->configurationService->getConfiguration($microsite_id);
            $tables_id_aux = null;
            foreach ($tables_id as $id) {
                if ($tables_id_aux !== null) {
                    $tables_id_aux = $tables_id_aux . "," . $id;
                } else {
                    $tables_id_aux = $id;
                }
            }
            ;
            $dateExpire = Carbon::now($this->timezone)->addMinutes($this->timeTolerance)->toDateTimeString();
            $token      = $this->tokenAuth;
            // $token               = Hash::make($tokenAuth);
            if ($update) {
                $reservationTemporal->expire    = $dateExpire;
                $reservationTemporal->zone_id   = $zone_id;
                $reservationTemporal->tables_id = $tables_id_aux;
                $reservationTemporal->token     = $token;
                $reservationTemporal->save();
            } else {
                $reservationTemporal['zone_id']   = $zone_id;
                $reservationTemporal['tables_id'] = $tables_id_aux;
                $reservationTemporal['token']     = $token;
                $reservationTemporal['expire']    = $dateExpire;
                res_table_reservation_temp::create($reservationTemporal);
            }
            return $reservationTemporal;
        } else {
            $dateC = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $this->timezone)->addDay($next_day);
            abort(500, "No se encontro ninguna mesa disponible para " . $num_guest . " personas el dia " . $dateC->format('l jS \\of F Y h:i:s A'));
        }
    }
}
