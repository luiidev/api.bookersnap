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
    private $id_status_finish    = 18;
    private $durationTimeAux     = "01:30:00";
    private $minCombinationTable = 3;
    private $maxPeople           = 15;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, ConfigurationService $ConfigurationService)
    {
        $this->calendarService      = $CalendarService;
        $this->turnService          = $TurnService;
        $this->configurationService = $ConfigurationService;
    }

    public function testArrayDay(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        /**
         * Busca la configuración del micrositio para determinar variables globales de configuracion
         * @var [id_microsite]
         */
        // $configuration             = $this->configurationService->getConfiguration($microsite_id);
        // $this->durationTimeAux     = $configuration->time_tolerance;
        // $this->minCombinationTable = $configuration->max_table;
        // $this->maxPeople           = $configuration->max_people;
        $indexHourInit = $this->defineIndexHour($next_day, $hour);
        if ($this->maxPeople < $num_guests) {
            abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        }

        $timeForTable = new TimeForTable;
        $arrayTestMid = collect();
        $results      = $this->getAvailabilityBasic($microsite_id, $date, $hour, $num_guests, $zone_id, $indexHourInit);
        if (count($results) > 0) {
            $arrayTestMid->push($results);
        } else {
            $arrayTestMid->push(["hour" => $hour, "tables" => null]);
        }

        if ($next_day == 0) {
            $arrayTestUp = collect();
            $indexUpHour = $indexHourInit + 1;
            while ($indexUpHour <= 119) {
                $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $timeForTable->indexToTime($indexUpHour), $num_guests, $zone_id, $indexUpHour);
                if (count($resultsUp) > 0) {
                    if ($arrayTestUp->count() < 2) {
                        $arrayTestUp->push($resultsUp);
                    } else {
                        $indexUpHour = 120;
                        break;
                    }
                }
                $indexUpHour++;
            }
            if ($arrayTestUp->count() < 2) {
                $countUp    = $arrayTestUp->count();
                $indexUpAux = $indexHourInit + 1;
                for ($i = $countUp; $i < 2; $i++) {
                    if ($indexUpAux <= 119) {
                        $arrayTestUp->push(["hour" => $timeForTable->indexToTime($indexUpAux), "tables" => null]);
                        $indexUpAux++;
                    } else {
                        $arrayTestUp->push(["hour" => null, "tables" => null]);
                    }
                }
            }
            $arrayTestDown = collect();
            for ($i = 1; $i <= 2; $i++) {
                $arrayTestDown->prepend(["hour" => null, "tables" => null]);
            }
            return array_merge($arrayTestDown->toArray(), $arrayTestMid->toArray(), $arrayTestUp->toArray());
        } else {
            $arrayTestUp = collect();
            $indexUpHour = $indexHourInit + 1;
            while ($indexUpHour <= 119) {
                $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $timeForTable->indexToTime($indexUpHour), $num_guests, $zone_id, $indexUpHour);
                if (count($resultsUp) > 0) {
                    if ($arrayTestUp->count() < 2) {
                        $arrayTestUp->push($resultsUp);
                    } else {
                        $indexUpHour = 120;
                        break;
                    }
                }
                $indexUpHour++;
            }

            $arrayTestDown = collect();
            $indexDownHour = $indexHourInit - 1;
            while ($indexDownHour >= 0) {
                $resultsDown = $this->getAvailabilityBasic($microsite_id, $date, $timeForTable->indexToTime($indexDownHour), $num_guests, $zone_id, $indexDownHour);
                if (count($resultsDown) > 0) {
                    if ($arrayTestDown->count() < 2) {
                        $arrayTestDown->prepend($resultsDown);
                    } else {
                        $indexDownHour = -1;
                        break;
                    }
                }
                $indexDownHour--;
            }
            if ($arrayTestDown->count() < 2) {
                $countDown    = $arrayTestDown->count();
                $indexDownAux = $indexHourInit - 1;
                for ($i = $countDown; $i < 2; $i++) {
                    if ($indexDownAux >= 0) {
                        $arrayTestDown->prepend(["hour" => $timeForTable->indexToTime($indexDownAux), "tables" => null]);
                        $indexDownAux--;
                    } else {
                        $arrayTestDown->prepend(["hour" => null, "tables" => null]);
                    }
                }
            }
            if ($arrayTestUp->count() < 2) {
                $countUp    = $arrayTestUp->count();
                $indexUpAux = $indexHourInit + 1;
                for ($i = $countUp; $i < 2; $i++) {
                    if ($indexUpAux <= 119) {
                        $arrayTestUp->push(["hour" => $timeForTable->indexToTime($indexUpAux), "tables" => null]);
                        $indexUpAux++;
                    } else {
                        $arrayTestUp->push(["hour" => null, "tables" => null]);
                    }
                }
            }

            return array_merge($arrayTestDown->toArray(), $arrayTestMid->toArray(), $arrayTestUp->toArray());
        }
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $indexHour)
    {
        // $this->defineIndexHour($next_day, "$hour");
        // return $this->indexHour;
        //Max cantidad de usuario
        //max cantidad de mesas por reserva

        $timeFoTable                = new TimeForTable;
        $availabilityTables         = collect();
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $availabilityTablesId       = [];
        $availabilityTablesIdFinal  = [];

        list($h, $m, $s)          = explode(":", $hour);
        list($year, $month, $day) = explode("-", $date);
        list($hd, $md, $sd)       = explode(":", $this->durationTimeAux);
        $startHour                = Carbon::create($year, $month, $day, $h, $m, $s);
        $endHour                  = Carbon::create($year, $month, $day, $h, $m, $s)->addHours($hd)->addMinutes($md)->addSeconds($sd);
        // return [$startHour->toDateTimeString(), $endHour->toDateTimeString()];
        // return $endHour->toDateTimeString();
        //Retorna los turnos filtrados por fecha de un micrositio
        $turnsFilter = $this->calendarService->getList($microsite_id, $year, $month, $day);

        //Buscar las mesas disponibles en los turnos filtrados
        foreach ($turnsFilter as $turn) {
            $availabilityTables->push($this->turnService->getListTable($turn['turn']['id'], $zone_id));
        };
        //Devuelve las mesas filtradas por el tipo de reservacion
        $availabilityTablesFilter = $this->getFilterTablesGuest($availabilityTables, $indexHour);
        if ($availabilityTablesFilter->isEmpty()) {
            return [];
        }

        $availabilityTablesFilter;
        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $availabilityTablesId = collect($availabilityTablesFilter)->pluck('id');

        //Devuelve id de las mesas filtradas que estan bloquedadas en una fecha y hora
        $ListBlocks = $this->getTableBlock($availabilityTablesId->toArray(), $date, $startHour->toDateTimeString(), $endHour->toDateTimeString());

        //Devuelve id de las mesas filtradas que estan reservadas en una fecha y hora
        $ListReservations = $this->getTableReservation($availabilityTablesId->toArray(), $date, $startHour->toDateTimeString(), $endHour->toDateTimeString());

        $unavailabilityTablesFilter = collect(array_merge($ListBlocks, $ListReservations))->unique();

        $availabilityTablesId = $availabilityTablesId->diff($unavailabilityTablesFilter);

        //Filtrar de las mesas disponibles la cantidad de usuarios
        $availabilityTablesIdFinal = $this->availabilityTablesIdFinal($availabilityTablesId->toArray(), $num_guests);

        if ($availabilityTablesIdFinal->count() > 0) {
            return ["hour" => $hour, "tables" => $availabilityTablesIdFinal->first()];
            // return ['tables_inicial' => $availabilityTablesId->values()->all(), 'tables final guest' => $availabilityTablesIdFinal->values()->all(), 'tables_indisponibles' => $unavailabilityTablesFilter, 'blocks' => $ListBlocks, 'reservatios' => $ListReservations];
        } else {
            $availabilityTablesIdFinal = $this->algoritmoAvailability($availabilityTablesId->toArray(), $num_guests);
            return ["hour" => $hour, "tables" => $availabilityTablesIdFinal];
        }

    }

    public function getTableBlock(array $tables_id, string $date, string $hourI, string $hourF)
    {
        $listBlock = [];
        $blocks    = BlockTable::whereIn('res_table_id', $tables_id)->with(['block' => function ($query) use ($date, $hourI, $hourF) {
            $query->where('start_date', '=', $date)
                ->whereRaw("concat(start_date,' ',start_time) <= ?", array($hourI))
                ->whereRaw("concat(start_date,' ',end_time) >= ?", array($hourI))
                ->orwhereRaw("concat(start_date,' ',start_time) < ?", array($hourF))
                ->whereRaw("concat(start_date,' ',end_time) >= ?", array($hourF));
        }])->get();
        // return $blocks;
        $listBlock = $blocks->reject(function ($value, $key) {
            return $value->block == null;
        });
        return $listBlock->pluck('res_table_id')->unique()->values()->all();
    }

    public function getTableReservation(array $tables_id, string $date, string $hourI, string $hourF)
    {
        $listReservation = [];
        $reservations    = res_table_reservation::whereIn('res_table_id', $tables_id)->with(['reservation' => function ($query) use ($date, $hourI, $hourF) {
            $query->where('date_reservation', '=', $date)
                ->where('res_reservation_status_id', '<>', $this->id_status_finish)
                ->whereRaw("concat(date_reservation,' ',hours_reservation) <= ?", array($hourI))
                ->whereRaw("addtime(concat(date_reservation,' ',hours_reservation),hours_duration) >= ?", array($hourI))
                ->orwhereRaw("concat(date_reservation,' ',hours_reservation) < ?", array($hourF))
                ->whereRaw("addtime(concat(date_reservation,' ',hours_reservation),hours_duration) >= ?", array($hourF));

        }])->get();
        // return $reservations;
        $listReservation = $reservations->reject(function ($value) use ($hourF) {
            return $value->reservation == null;
        });
        return $listReservation->pluck('res_table_id')->unique()->values()->all();
    }

    public function getFilterTablesGuest($availabilityTables, int $indexHour)
    {
        $availabilityTablesFilter = collect();
        foreach ($availabilityTables as $tables) {
            foreach ($tables as $table) {
                if ($table['availability'][$indexHour]['rule_id'] >= 2) {
                    $availabilityTablesFilter->push(collect($table));
                }
            }
        }
        return $availabilityTablesFilter;
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
        if ($test != null) {
            return $test;
        } else {
            return null;
        }
    }

    public function test($collect, int $num_guests)
    {
        $array = collect();
        foreach ($collect as $table) {
            if ($table->max_cover >= $num_guests || $num_guests == 0) {
                if ($table->min_cover <= $num_guests && $num_guests <= $table->max_cover) {
                    $array->push($table);
                    $num_guests = 0;
                }
                if ($num_guests == 0) {
                    if ($array->count() <= $this->minCombinationTable) {
                        return $array->pluck('id');
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
                return 119;
            } else {
                return $indexHour + 96;
            }
        } else {
            return $timeFoTable->timeToIndex($hour);
        }
    }
}
