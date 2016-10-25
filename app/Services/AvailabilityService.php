<?php

namespace App\Services;

use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\res_table;
use App\res_table_reservation;
use App\Services\CalendarService;
use Carbon\Carbon;

class AvailabilityService
{
    private $calendarService;
    private $turnService;
    private $indexHour;
    private $id_status_finish    = 18;
    private $durationTimeAux     = "01:30:00";
    private $minCombinationTable = 3;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService)
    {
        $this->calendarService = $CalendarService;
        $this->turnService     = $TurnService;
    }

    public function testArrayDay()
    {
        //Esta funcion devolvera un array de 5 horarios correlativas de disponibilidad
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        $timeFoTable                = new TimeForTable;
        $availabilityTables         = collect([]);
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $availabilityTablesId       = [];
        $availabilityTablesIdFinal  = [];

        // $indexHour = $timeFoTable->timeToIndex($hour);
        // echo $indexHour;
        $this->defineIndexHour($next_day, $hour);
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
        $availabilityTablesFilter = $this->getFilterTablesGuest($availabilityTables, $num_guests);
        // return $availabilityTablesFilter;
        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $availabilityTablesId = collect($availabilityTablesFilter)->pluck('id');

        //Devuelve id de las mesas filtradas que estan bloquedadas en una fecha y hora
        $ListBlocks = $this->getTableBlock($availabilityTablesId->toArray(), $date, $hour);

        //Devuelve id de las mesas filtradas que estan reservadas en una fecha y hora
        $ListReservations = $this->getTableReservation($availabilityTablesId->toArray(), $date, $hour);

        $unavailabilityTablesFilter = collect(array_merge($ListBlocks, $ListReservations))->unique();

        $availabilityTablesId = $availabilityTablesId->diff($unavailabilityTablesFilter);

        //Filtrar de las mesas disponibles la cantidad de usuarios
        $availabilityTablesIdFinal = $this->availabilityTablesIdFinal($availabilityTablesId->toArray(), $num_guests);

        if ($availabilityTablesIdFinal->count() > 0) {
            return ['tables_inicial' => $availabilityTablesId->values()->all(), 'tables final' => $availabilityTablesIdFinal->values()->all(), 'tables_indisponibles' => $unavailabilityTablesFilter, 'blocks' => $ListBlocks, 'reservatios' => $ListReservations];
            return $availabilityTablesIdFinal->first();
        } else {
            return $availabilityTablesIdFinal = $this->algoritmoAvailability($availabilityTablesId->toArray(), $num_guests);
        }

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
                ->where('res_reservation_status_id', '<>', $this->id_status_finish);
        }])->get();
        // return $reservations;
        $listReservation = $reservations->reject(function ($value) use ($hour) {
            $sinReserva = true;

            if ($value->reservation != null) {
                $sinReserva = $this->compareTime($value->reservation->hours_reservation, $value->reservation->hours_duration, $hour);
            }
            return $value->reservation == null || $sinReserva;
        });
        // return $listReservation;
        return $listReservation->pluck('res_table_id')->unique()->values()->all();
    }

    public function getFilterTablesGuest($availabilityTables, int $num_guests)
    {
        $availabilityTablesFilter = collect([]);
        foreach ($availabilityTables as $tables) {
            foreach ($tables as $table) {
                if ($table['availability'][$this->indexHour]['rule_id'] >= 2) {
                    $availabilityTablesFilter->push(collect($table)->forget('availability'));
                }
            }
        }

        return $availabilityTablesFilter;
    }

    public function compareTime(string $startTime, string $duration, string $hour)
    {
        list($h, $m, $s)    = explode(":", $hour);
        list($hs, $ms, $ss) = explode(":", $startTime);
        list($hd, $md, $sd) = explode(":", $duration);
        list($ha, $ma, $sa) = explode(":", $this->durationTimeAux);

        //Hora actual
        $hourA = Carbon::createFromTime($h, $m, $s);
        //Hora inicial de reservacion
        $startTimeR = Carbon::createFromTime($hs, $ms, $ss);
        //Hora final de reservacion
        $endTimeR = Carbon::createFromTime($hs, $ms, $ss)->addHours($hd)->addMinutes($md)->addSeconds($sd);
        //Hora actual aumentado en el rango de duracion promedio de reservacion 01:30:00
        $startTimeAuxI = Carbon::createFromTime($h, $m, $s)->addHours($ha)->addMinutes($ma)->addSeconds($sa);

        //comparo que la hora actual sea mayor = que la hora inicial de reservación
        $mayorRangoMin = $startTimeR->toTimeString() <= $hourA->toTimeString();
        //comparo que la hora actual sea menor = que la hora final de reservación
        $menorRangoMax = $endTimeR->toTimeString() >= $hourA->toTimeString();
        //comparo que la hora inicial aumentado en la hora sea mayor que la hora inicial de reservacion
        $mayorRangoMinAux = $startTimeAuxI->toTimeString() > $startTimeR->toTimeString();
        //comparo que la hora inicial aumentado en la hora inicial sea menor que la hora final de reservacion
        $menorRangoMaxAux = $startTimeAuxI->toTimeString() < $endTimeR->toTimeString();
        if ($mayorRangoMin == true && $menorRangoMax == true) {
            return false;
        } elseif ($mayorRangoMinAux == true && $menorRangoMaxAux == true) {
            return false;
        } else {
            return true;
        }
    }

    public function availabilityTablesIdFinal(array $listId, int $num_guests)
    {
        $availabilityNumGuest = res_table::whereIn('id', $listId)
            ->where('min_cover', '<=', $num_guests)
            ->where('max_cover', '>=', $num_guests)->get();
        return $availabilityNumGuest->pluck('id');
    }

    public function algoritmoAvailability(array $listId, int $num_guests)
    {
        $availabilityNumGuest = res_table::whereIn('id', $listId)
            ->where('min_cover', '<=', $num_guests)->orderby('max_cover', 'desc')->get();

        $test = $this->test($availabilityNumGuest, $num_guests);

        return $test;
    }

    public function test($collect, int $num_guests)
    {
        $array = collect([]);
        // return $collect;
        // $collectAux = $collect->reject(function ($item) {
        //     return $item->max_cover == 10;
        // });
        // return $collectAux;
        // return $collect;
        foreach ($collect as $table) {
            if ($table->max_cover >= $num_guests || $num_guests == 0) {
                if ($table->min_cover <= $num_guests && $num_guests <= $table->max_cover) {
                    $array->push($table);
                    $num_guests = 0;
                }
                if ($num_guests == 0) {
                    if ($array->count() <= $this->minCombinationTable) {
                        return $array;
                    } else {
                        return null;
                    }
                }
            } else {
                $array->push($table);
                $num_guests = $num_guests - $table->max_cover;
            }
        }

    }

    public function defineIndexHour($next_day, $hour)
    {
        $timeFoTable = new TimeForTable;
        if ($next_day == 1) {
            $indexHour      = $timeFoTable->timeToIndex($hour);
            $indexHourLimit = $timeFoTable->timeToIndex("06:00:00");
            if ($indexHour >= $indexHourLimit) {
                $this->indexHour = 119;
            } else {
                $this->indexHour = $indexHour + 96;
            }
        } else {
            $this->indexHour = $timeFoTable->timeToIndex($hour);
        }
    }
}
