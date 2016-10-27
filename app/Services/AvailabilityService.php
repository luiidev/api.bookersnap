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
    private $id_status_finish = 18;
    private $durationTimeAux  = "01:30:00";
    private $minCombinationTable;
    private $maxPeople;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, ConfigurationService $ConfigurationService, TimeForTable $TimeForTable)
    {
        $this->calendarService      = $CalendarService;
        $this->turnService          = $TurnService;
        $this->configurationService = $ConfigurationService;
        $this->timeForTable         = $TimeForTable;
    }

    public function searchAvailabilityDay(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        if ($next_day == 1) {
            if ($hour > "05:45:00") {
                return "Rango Incorrecto";
            }
        }

        $configuration = $this->configurationService->getConfiguration($microsite_id);
        // return $configuration;
        $this->minCombinationTable = $configuration->max_table;
        $this->maxPeople           = $configuration->max_people;
        if ($this->maxPeople < $num_guests) {
            abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        }

        $hourI          = $hour;
        $hours          = $this->formatActualHour($hour, "America/Lima");
        $hour           = $hours->get("hour");
        $indexHourInitI = $this->defineIndexHour($next_day, $hourI);
        $indexHourInit  = $this->defineIndexHour($next_day, $hours->get("hour"));

        // return ["hourI" => $hourI, "hour" => $hours->get("hour"), "hourA" => $hours->get("hourA")];
        $arrayMid   = collect();
        $resultsMid = [];
        if ($hourI === $hour) {
            // dd("TEST");
            $resultsMid = $this->getAvailabilityBasic($microsite_id, $date, $hourI, $num_guests, $zone_id, $indexHourInitI);
        }
        if (count($resultsMid) > 0) {
            $arrayMid->push($resultsMid);
        } else {
            $arrayMid->push(["hour" => $hourI, "tables" => null]);
        }

        if ($next_day == 0) {
            $indexHourActualAux = $this->defineIndexHour($next_day, $hours->get("hourA"));
            $arrayUp            = $this->searchUpAvailability($indexHourInit, $microsite_id, $date, $num_guests, $zone_id);
            $arrayDown          = $this->searchDownAvailability($indexHourInit, $microsite_id, $date, $num_guests, $zone_id, $indexHourActualAux);
            if ($arrayUp->count() < 2) {
                $arrayUp = $this->addUpAvailavility($arrayUp, $indexHourInit);
            }
            if ($arrayDown->count() < 2) {
                $arrayDown = $this->addDownAvailavility($arrayDown, $indexHourInit - 1, $indexHourActualAux);
            }
        } else {
            $arrayUp   = $this->searchUpAvailability($indexHourInitI, $microsite_id, $date, $num_guests, $zone_id);
            $arrayDown = $this->searchDownAvailability($indexHourInitI, $microsite_id, $date, $num_guests, $zone_id, 0);
            if ($arrayUp->count() < 2) {
                $arrayUp = $this->addUpAvailavility($arrayUp, $indexHourInitI);
            }
            if ($arrayDown->count() < 2) {
                $arrayDown = $this->addDownAvailavility($arrayUp, $indexHourInitI, 0);
            }
        }
        return array_merge($arrayDown->toArray(), $arrayMid->toArray(), $arrayUp->toArray());
    }

    public function formatActualHour(string $hour, string $timezone)
    {
        $hourA         = Carbon::now()->tz($timezone);
        $hourA->second = 0;
        if ($hourA->minute < 15) {
            $hourA->minute = 15;
        } elseif ($hourA->minute < 30) {
            $hourA->minute = 30;
        } elseif ($hourA->minute < 45) {
            $hourA->minute = 45;
        } else {
            $hourA->addHour();
            $hourA->minute = 0;
        }
        // dd($hourA);
        $hourAux = Carbon::parse($hourA);
        if ($hour < $hourAux->toTimeString()) {
            $hour = $hourAux->toTimeString();
        }
        // dd($hourA);
        return collect(["hour" => $hour, "hourA" => $hourA->toTimeString()]);
    }

    public function searchUpAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id)
    {
        $arrayUp     = collect();
        $indexUpHour = $indexHourInit + 1;
        while ($indexUpHour <= 119) {
            $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $this->timeForTable->indexToTime($indexUpHour), $num_guests, $zone_id, $indexUpHour);
            if (count($resultsUp) > 0) {
                if ($arrayUp->count() < 2) {
                    $arrayUp->push($resultsUp);
                } else {
                    $indexUpHour = 120;
                    break;
                }
            }
            $indexUpHour++;
        }
        return $arrayUp;
    }
    public function searchDownAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id, int $indexHourActualAux)
    {
        $arrayDown     = collect();
        $indexDownHour = $indexHourInit - 1;
        while ($indexDownHour >= $indexHourActualAux) {
            $resultsDown = $this->getAvailabilityBasic($microsite_id, $date, $this->timeForTable->indexToTime($indexDownHour), $num_guests, $zone_id, $indexDownHour);
            if (count($resultsDown) > 0) {
                if ($arrayDown->count() < 2) {
                    $arrayDown->prepend($resultsDown);
                } else {
                    $indexDownHour = -1;
                    break;
                }
            }
            $indexDownHour--;
        }
        return $arrayDown;
    }
    public function addUpAvailavility($arrayUp, int $indexHourInit)
    {
        $countUp    = $arrayUp->count();
        $indexUpAux = $indexHourInit + 1;
        for ($i = $countUp; $i < 2; $i++) {
            if ($indexUpAux <= 119) {
                $arrayUp->push(["hour" => $this->timeForTable->indexToTime($indexUpAux), "tables" => null]);
                $indexUpAux++;
            } else {
                $arrayUp->push(["hour" => null, "tables" => null]);
            }
        }
        return $arrayUp;
    }
    public function addDownAvailavility($arrayDown, int $indexHourInit, int $indexHourActualAux)
    {
        $countDown    = $arrayDown->count();
        $indexDownAux = $indexHourInit - 1;
        for ($i = $countDown; $i < 2; $i++) {
            if ($indexDownAux >= $indexHourActualAux) {
                $arrayDown->prepend(["hour" => $this->timeForTable->indexToTime($indexDownAux), "tables" => null]);
                $indexDownAux--;
            } else {
                $arrayDown->prepend(["hour" => null, "tables" => null]);
            }
        }
        return $arrayDown;
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $indexHour)
    // public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        // $this->defineIndexHour($next_day, "$hour");
        // return $this->indexHour;
        //Max cantidad de usuario
        //max cantidad de mesas por reserva
        // $indexHour                  = $this->defineIndexHour($next_day, $hour);
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
        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $availabilityTablesId = $availabilityTablesFilter->pluck('id');

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
        $combination = $this->combination($availabilityNumGuest, $num_guests);
        if ($combination != null) {
            return $combination;
        } else {
            return null;
        }
    }

    public function combination($collect, int $num_guests)
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
        if ($next_day == 1) {
            $indexHour      = $this->timeForTable->timeToIndex($hour);
            $indexHourLimit = $this->timeForTable->timeToIndex("06:00:00");
            if ($indexHour >= $indexHourLimit) {
                return 119;
            } else {
                return $indexHour + 96;
            }
        } else {
            return $this->timeForTable->timeToIndex($hour);
        }
    }
}
