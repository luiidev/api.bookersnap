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

    public function createReservationTemporal(int $user_id, int $microsite_id, string $hour, string $date, int $num_guest, int $zone_id = null, string $timezone, $reservation, int $ev_event_id = null, string $tokenAuth, int $next_day, int $num_guests)
    {
        // return $reservation;
        // return $ev_event_id;
        // $configuration = $this->configurationService->getConfiguration($microsite_id);
        // if ($configuration->maxPeople < $num_guests) {
        //     abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        // }
        // $fakeDate = Carbon::create(2016, 11, 29, 17, null, null, $timezone);
        // Carbon::setTestNow($fakeDate);

        $this->timezone     = $timezone;
        $this->tokenAuth    = $tokenAuth;
        $dateExpirePrevious = Carbon::now($this->timezone)->subMinutes($this->timeTolerance)->toDateTimeString();
        $exists             = res_table_reservation_temp::where('token', $this->tokenAuth)->where('expire', '>=', $dateExpirePrevious)->get();
        if ($exists->isEmpty()) {
            if (!isset($zone_id)) {
                $zone_id = $reservation[2]['zone_id'];
            }
            // return $reservation[2];
            $tables_id = $reservation[2]['tables_id'];
            // $ev_event_id = $reservation[2]['ev_event_id'];
            // return isset($reservation[2]['standing_people']) ? $reservation[2]['standing_people'] : null;
            $standing_people  = isset($reservation[2]['standing_people']) ? $reservation[2]['standing_people'] : null;
            $aux_num_standing = null;
            // return !($standing_people && $standing_people['availability_standing']);
            if ($standing_people) {
                if (!$standing_people['availability_standing']) {
                    abort(500, "No hay disponibilidad de mesas");
                } else {
                    $aux_num_standing = $standing_people['num_guest_availability'];
                }
            }

            $update              = false;
            $reservationTemporal = [
                'hour'            => $hour,
                'date'            => $date,
                'num_guest'       => $num_guest,
                'user_id'         => $user_id,
                'ev_event_id'     => $ev_event_id,
                'ms_microsite_id' => $microsite_id,
                'standing_people' => $aux_num_standing,
                'next_day'        => $next_day,
            ];
            // return $reservationTemporal;
            return $this->createUpdateTemp($zone_id, $tables_id, $reservationTemporal, $update, $date, $hour, $num_guest, $next_day, $dateExpirePrevious);
        } else {

            $reservationTemporal = $exists->first();
            if ($reservationTemporal->hour == $hour && $reservationTemporal->date == $date && $reservationTemporal->num_guest == $num_guest && $reservationTemporal->zone_id == $zone_id && $reservationTemporal->next_day == $next_day) {
                return $reservationTemporal;
            } else {
                // return "test";
                if (isset($zone_id)) {
                    $availability = $this->availabilityService->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $this->timezone);
                } else {
                    $availability = $this->availabilityService->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $this->timezone);
                    $zone_id      = $reservation[2]['zone_id'];
                }
                $tables_id   = $availability[2]['tables_id'];
                $ev_event_id = @$availability[2]['ev_event_id'];
                // return           = $availability[2];
                $standing_people = isset($availability[2]['standing_people']) ? $availability[2]['standing_people']['num_guest_availability'] : null;

                $update                               = true;
                $reservationTemporal->hour            = $hour;
                $reservationTemporal->date            = $date;
                $reservationTemporal->num_guest       = $num_guest;
                $reservationTemporal->user_id         = $user_id;
                $reservationTemporal->ev_event_id     = $ev_event_id;
                $reservationTemporal->ms_microsite_id = $microsite_id;
                $reservationTemporal->standing_people = $standing_people;
                $reservationTemporal->next_day        = $next_day;

                return $this->createUpdateTemp($zone_id, $tables_id, $reservationTemporal, $update, $date, $hour, $num_guest, $next_day, $dateExpirePrevious);

            }
        }

    }
    public function createUpdateTemp(int $zone_id = null, $tables_id = null, $reservationTemporal, bool $update, string $date, string $hour, int $num_guest, int $next_day, string $dateExpirePrevious)
    {
        // $configuration = $this->configurationService->getConfiguration($microsite_id);
        // if ($configuration->maxPeople < $num_guests) {
        //     abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        // }
        // return []
        if (isset($zone_id) && isset($tables_id)) {
            // return $configuration = $this->configurationService->getConfiguration($microsite_id);
            $tables_id_aux = null;
            foreach ($tables_id as $id) {
                if ($tables_id_aux !== null) {
                    $tables_id_aux = $tables_id_aux . "," . $id;
                } else {
                    $tables_id_aux = $id;
                }
            };

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
            // return $reservationTemporal['standing_people'];
            if (isset($reservationTemporal['standing_people'])) {
                $dateExpire = Carbon::now($this->timezone)->addMinutes($this->timeTolerance)->toDateTimeString();
                $token      = $this->tokenAuth;
                // $token               = Hash::make($tokenAuth);
                if ($update) {
                    $reservationTemporal->expire          = $dateExpire;
                    $reservationTemporal->zone_id         = $zone_id;
                    $reservationTemporal->tables_id       = null;
                    $reservationTemporal->standing_people = $reservationTemporal['standing_people'];
                    $reservationTemporal->token           = $token;
                    $reservationTemporal->save();
                } else {
                    $reservationTemporal['zone_id']   = $zone_id;
                    $reservationTemporal['tables_id'] = null;
                    // $reservationTemporal['standing_people'] = $reservationTemporal['standing_people'];
                    $reservationTemporal['token']  = $token;
                    $reservationTemporal['expire'] = $dateExpire;
                    res_table_reservation_temp::create($reservationTemporal);
                }
                return $reservationTemporal;
            } else {

                $this->deleteTemporal($dateExpirePrevious, $this->tokenAuth);
                $dateC = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $this->timezone)->addDay($next_day);
                abort(500, "No se encontro ninguna mesa disponible para " . $num_guest . " personas el dia " . $dateC->format('l jS \\of F Y h:i:s A'));
            }
        }
    }

    public function deleteTemporal(string $dateExpirePrevious, string $token)
    {
        res_table_reservation_temp::where('token', $token)->where('expire', '>=', $dateExpirePrevious)->delete();
    }

    public function getTimeTolerance()
    {
        return $this->timeTolerance;
    }

    public function getTempReservation(String $token)
    {
        return res_table_reservation_temp::where("token", $token)->whereRaw(" now() <= expire  + interval 5 minute")->first();
    }
}
