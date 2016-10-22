<?php

namespace App\Services;

use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\res_table_reservation;
use App\Services\CalendarService;
use Carbon\Carbon;

class AvailabilityService
{
    private $calendarService;
    private $turnService;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService)
    {
        $this->calendarService = $CalendarService;
        $this->turnService     = $TurnService;
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id)
    {
        $timeFoTable                = new TimeForTable;
        $availabilityTables         = collect([]);
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $availabilityTablesIdFinal  = [];
        $indexHour                  = $timeFoTable->timeToIndex($hour);
        // $time        = $timeFoTable->indexToTime($index);
        list($year, $month, $day) = explode("-", $date);

        //Retorna los turnos filtrados por fecha de un micrositio
        // Route::get('calendar/{date}/shifts', 'CalendarController@index');
        // public function getList(int $microsite_id, int $year, int $month, int $day = null)
        $turnsFilter = $this->calendarService->getList($microsite_id, $year, $month, $day);

        //Buscar las mesas disponibles en los turnos filtrados
        foreach ($turnsFilter as $turn) {
            $availabilityTables->push($this->turnService->getListTable($turn['turn']['id'], $zone_id));
        };
        //Devuelve las mesas filtradas por el tipo de reservacion y numero de invitados
        $availabilityTablesFilter = $this->getFilterTablesGuest($indexHour, $availabilityTables, $num_guests);
        // return $availabilityTablesFilter;
        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $availabilityTablesId = collect($availabilityTablesFilter)->pluck('id');

        //Devuelve id de las mesas filtradas que estan bloquedadas en una fecha y hora
        $ListBlocks = $this->getTableBlock($availabilityTablesId->toArray(), $date, $hour);

        //Devuelve id de las mesas filtradas que estan reservadas en una fecha y hora
        $ListReservations = $this->getTableReservation($availabilityTablesId->toArray(), $date, $hour);

        $unavailabilityTablesFilter = collect(array_merge($ListBlocks, $ListReservations))->unique();

        $availabilityTablesIdFinal = $availabilityTablesId->diff($unavailabilityTablesFilter);

        // if ($availabilityTablesIdFinal->count() > 0) {
        //     return true;
        // } else {
        //     return false;
        // }

        return ['tables_inicial' => $availabilityTablesId, 'tables final' => $availabilityTablesIdFinal->values()->all(), 'tables_indisponibles' => $unavailabilityTablesFilter, 'blocks' => $ListBlocks, 'reservatios' => $ListReservations];
    }

    public function getTableBlock(array $tables_id, string $date, string $hour)
    {
        $listBlock = [];
        $blocks    = BlockTable::whereIn('res_table_id', $tables_id)->with(['block' => function ($query) use ($date, $hour) {
            $query->where('start_date', '=', $date)
                ->where('start_time', '<=', $hour)
                ->where('end_time', '>=', $hour);
        }])->get();
        $listBlock = $blocks->reject(function ($value, $key) {
            return $value->block == null;
        });

        return $listBlock->pluck('res_table_id')->unique()->values()->all();

    }

    public function getTableReservation(array $tables_id, string $date, string $hour)
    {
        $listReservation = [];
        $reservations    = res_table_reservation::whereIn('res_table_id', $tables_id)->with(['reservation' => function ($query) use ($date, $hour) {
            $query->where('date_reservation', '=', $date)
                ->where('hours_reservation', '<=', $hour)
                ->where('hours_reservation', '>=', $hour);
        }])->get();

        $listReservation = $reservations->reject(function ($value) use ($hour) {
            $mayor = true;
            if ($value->reservation != null) {
                $mayor = $this->compareMayorTime($value->reservation->hours_reservation, $value->reservation->hours_duration, $hour);
            }
            return $value->reservation == null && $mayor;
        });

        return $listReservation->pluck('res_table_id')->unique()->values()->all();
    }

    public function getFilterTablesGuest(int $indexHour, $availabilityTables, int $num_guests)
    {
        $availabilityTablesFilter = collect([]);
        foreach ($availabilityTables as $tables) {
            foreach ($tables as $table) {
                if ($table['availability'][$indexHour]['rule_id'] >= 2) {
                    // if ($table['min_cover'] <= $num_guests && $num_guests <= $table['max_cover']) {
                    $availabilityTablesFilter->push(collect($table)->forget('availability'));
                    // }
                }
            }
        }

        return $availabilityTablesFilter;
    }

    public function compareMayorTime(string $startTime, string $duration, string $hour)
    {
        $startTime          = $startTime;
        $duration           = $duration;
        list($h, $m, $s)    = explode(":", $hour);
        list($hs, $ms, $ss) = explode(":", $startTime);
        list($hd, $md, $sd) = explode(":", $duration);
        $hourC              = Carbon::createFromTime($h, $m, $s);
        $startTimeC         = Carbon::createFromTime($hs, $ms, $ss);
        $endTimeC           = Carbon::createFromTime($hs, $ms, $ss);
        $endTimeC->addHours($hd);
        $endTimeC->addMinutes($md);
        $endTimeC->addSeconds($sd);
        return $endTimeC->toTimeString() <= $hourC->toTimeString();
    }
}
