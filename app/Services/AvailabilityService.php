<?php

namespace App\Services;

use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\res_table_reservation;
use App\Services\CalendarService;

class AvailabilityService
{
    private $calendarService;
    private $turnService;
    private $blockService;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, BlockService $BlockService)
    {
        $this->calendarService = $CalendarService;
        $this->turnService     = $TurnService;
        $this->blockService    = $BlockService;
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id)
    {
        $timeFoTable                = new TimeForTable;
        $availabilityTables         = [];
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $indexHour                  = $timeFoTable->timeToIndex($hour);
        // $time        = $timeFoTable->indexToTime($index);
        list($year, $month, $day) = explode("-", $date);

        //Retorna los turnos filtrados por fecha de un micrositio
        // Route::get('calendar/{date}/shifts', 'CalendarController@index');
        // public function getList(int $microsite_id, int $year, int $month, int $day = null)
        $turnsFilter = $this->calendarService->getList($microsite_id, $year, $month, $day);

        //Buscar las mesas disponibles en los turnos filtrados
        foreach ($turnsFilter as $key => $turn) {
            $availabilityTables[$key] = $this->turnService->getListTable($turn['turn']['id'], $zone_id);
        };

        //Devuelve las mesas filtradas por el tipo de reservacion y numero de invitados
        $availabilityTablesFilter = $this->getFilterTablesGuest($indexHour, $availabilityTables, $num_guests);

        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $ArrayTablesId = collect($availabilityTablesFilter)->pluck('id')->toArray();

        //Devuelve id de las mesas filtradas que estan bloquedadas en una fecha y hora
        $ListBlocks = $this->getTableBlock($ArrayTablesId, $date, $hour);

        //Devuelve id de las mesas filtradas que estan reservadas en una fecha y hora
        $ListReservations = $this->getTableReservation($ArrayTablesId, $date, $hour);

        $unavailabilityTablesFilter = collect(array_merge($ListBlocks, $ListReservations))->unique();

        // //Filtrar availabilityTablesFilter quitandole las mesas bloqueadas y reservadas;
        // foreach ($availabilityTablesFilter as $key => $table) {

        // }

        return ['id_tables' => $ArrayTablesId, 'tables' => $availabilityTablesFilter, 'tables_indisponibles' => $unavailabilityTablesFilter, 'blocks' => $ListBlocks, 'reservatios' => $ListReservations];
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
            $query->where('date_reservation', '=', $date);
            // ->where('start_time', '<=', $hour)
            // ->where('end_time', '>=', $hour);
        }])->get();
        $listReservation = $reservations->reject(function ($value) {
            return $value->reservation == null;
        });

        return $listReservation->pluck('res_table_id')->unique()->values()->all();
    }

    public function getFilterTablesGuest(int $indexHour, array $availabilityTables, int $num_guests)
    {
        foreach ($availabilityTables as $key => $tables) {
            foreach ($tables as $key => $table) {
                if ($table['availability'][$indexHour]['rule_id'] >= 1) {
                    if ($table['min_cover'] <= $num_guests && $num_guests <= $table['max_cover']) {
                        $availabilityTablesFilter[$key]['id']        = $table['id'];
                        $availabilityTablesFilter[$key]['name']      = $table['name'];
                        $availabilityTablesFilter[$key]['min_cover'] = $table['min_cover'];
                        $availabilityTablesFilter[$key]['max_cover'] = $table['max_cover'];
                        // $availabilityTablesFilter[$key]['availability'] = $table['availability'][$index];
                    }
                }
            }
        }

        return $availabilityTablesFilter;
    }
}
