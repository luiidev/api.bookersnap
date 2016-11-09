<?php

namespace App\Services;

use App\Entities\res_table_reservation_temp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class ReservationTemporalService
{
    private $hash          = "test";
    private $timeTolerance = 10;
    private $configurationService;
    public function __construct(ConfigurationService $ConfigurationService)
    {
        $this->configurationService = $ConfigurationService;
    }

    public function createReservationTemporal(int $user_id, int $microsite_id, string $hour, string $date, int $num_guest, int $zone_id, array $tables_id, int $ev_event_id)
    {
        // return $configuration = $this->configurationService->getConfiguration($microsite_id);
        $tables_id_aux = null;
        foreach ($tables_id as $id) {
            if ($tables_id_aux !== null) {
                $tables_id_aux = $tables_id_aux . "," . $id;
            } else {
                $tables_id_aux = $id;
            }
        };
        $dateExpire = Carbon::now("America/Lima")->addMinutes($this->timeTolerance)->toDateTimeString();
        $token      = Hash::make($dateExpire . $this->hash);
        // return Hash::check("122" . $this->hash, '$2y$10$RyRODK6pZDzmtqEPalBnWeW7938.FvJAjycX1pkrzdE/IPPSZ64yS');
        $reservationTemporal = ['hour' => $hour, 'date' => $date, 'num_guest' => $num_guest, 'zone_id' => $zone_id, 'user_id' => $user_id, 'tables_id' => $tables_id_aux, 'ev_event_id' => $ev_event_id, 'token' => $token, 'expire' => $dateExpire, 'ms_microsite_id' => $microsite_id];
        return res_table_reservation_temp::create($reservationTemporal);
    }
}
