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
    // private $indexHour;
    private $id_status_finish    = 18;
    private $durationTimeAux     = "01:30:00";
    private $minCombinationTable = 3;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService)
    {
        $this->calendarService = $CalendarService;
        $this->turnService     = $TurnService;
    }

    public function testArrayDay(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        $timeForTable = new TimeForTable;
        $carbon       = Carbon::now(-5);
        // if ($hour <= $carbon) {
        //     $hour = $carbon->toTimeString();
        // }
        // return $carbon->toTimeString();

        $arrayTestMid = collect();
        $results      = $this->getAvailabilityBasic($microsite_id, $date, $hour, $num_guests, $zone_id, $this->defineIndexHour($next_day, $hour));
        if (collect($results)->count() > 0) {
            $arrayTestMid->push([$results]);
        } else {
            $resultsAux = collect(["hour" => $hour, "tables" => null]);
            $arrayTestMid->push($resultsAux);
        }

        if ($next_day == 0) {
            $arrayTestUp = collect();
            $indexUpHour = $this->defineIndexHour($next_day, $hour) + 1;
            while ($indexUpHour <= 119) {
                $hourAuxUp = $timeForTable->indexToTime($indexUpHour);
                if ($indexUpHour <= 119) {
                    $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $hourAuxUp, $num_guests, $zone_id, $indexUpHour);
                    if (collect($resultsUp)->count() > 0) {
                        if ($arrayTestUp->count() < 2) {
                            $arrayTestUp->push($resultsUp);
                        } else {
                            $indexUpHour = 120;
                        }
                    }
                }
                $indexUpHour++;
            }
            if ($arrayTestUp->count() < 2) {
                $countUp    = $arrayTestUp->count();
                $indexUpAux = $this->defineIndexHour($next_day, $hour) + 1;
                for ($i = $countUp; $i < 2; $i++) {
                    if ($indexUpAux <= 119) {
                        $resultsAux2 = collect(["hour" => $timeForTable->indexToTime($indexUpAux), "tables" => null]);
                        $arrayTestUp->push($resultsAux2);
                        $indexUpAux++;
                    } else {
                        $resultsAux2 = collect(["hour" => null, "tables" => null]);
                        $arrayTestUp->push($resultsAux2);
                    }
                }
            }
            $arrayTestDown = collect();
            if ($arrayTestDown->count() < 2) {
                $countDown = $arrayTestDown->count();
                for ($i = $countDown; $i < 2; $i++) {
                    $resultsAux2 = collect(["hour" => null, "tables" => null]);
                    $arrayTestDown->prepend($resultsAux2);
                }
            }
            return array_merge($arrayTestDown->toArray(), $arrayTestMid->toArray(), $arrayTestUp->toArray());
        } else {
            $arrayTestUp = collect();
            $indexUpHour = $this->defineIndexHour($next_day, $hour) + 1;
            while ($indexUpHour <= 119) {
                $hourAuxUp = $timeForTable->indexToTime($indexUpHour);
                if ($indexUpHour <= 119) {
                    $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $hourAuxUp, $num_guests, $zone_id, $indexUpHour);
                    if (collect($resultsUp)->count() > 0) {
                        if ($arrayTestUp->count() < 2) {
                            $arrayTestUp->push($resultsUp);
                        } else {
                            $indexUpHour = 120;
                        }
                    }
                }
                $indexUpHour++;
            }

            $arrayTestDown = collect();
            $indexDownHour = $this->defineIndexHour($next_day, $hour) - 1;
            while ($indexDownHour >= 0) {
                $hourAuxDown = $timeForTable->indexToTime($indexDownHour);
                if ($indexDownHour >= 0) {
                    $resultsDown = $this->getAvailabilityBasic($microsite_id, $date, $hourAuxDown, $num_guests, $zone_id, $indexDownHour);
                    if (collect($resultsDown)->count() > 0) {
                        if ($arrayTestDown->count() < 2) {
                            $arrayTestDown->prepend($resultsDown);
                        } else {
                            $indexDownHour = -1;
                        }
                    }
                }
                $indexDownHour--;
            }
            if ($arrayTestDown->count() < 2) {
                $countDown    = $arrayTestDown->count();
                $indexDownAux = $this->defineIndexHour($next_day, $hour) - 1;
                for ($i = $countDown; $i < 2; $i++) {
                    if ($indexDownAux >= 0) {
                        $resultsAux2 = collect(["hour" => $timeForTable->indexToTime($indexDownAux), "tables" => null]);
                        $arrayTestDown->prepend($resultsAux2);
                        $indexDownAux--;
                    } else {
                        $resultsAux2 = collect(["hour" => null, "tables" => null]);
                        $arrayTestDown->prepend($resultsAux2);
                    }
                }
            }
            if ($arrayTestUp->count() < 2) {
                $countUp    = $arrayTestUp->count();
                $indexUpAux = $this->defineIndexHour($next_day, $hour) + 1;
                for ($i = $countUp; $i < 2; $i++) {
                    if ($indexUpAux <= 119) {
                        $resultsAux2 = collect(["hour" => $timeForTable->indexToTime($indexUpAux), "tables" => null]);
                        $arrayTestUp->push($resultsAux2);
                        $indexUpAux++;
                    } else {
                        $resultsAux2 = collect(["hour" => null, "tables" => null]);
                        $arrayTestUp->push($resultsAux2);
                    }
                }
            }

            return array_merge($arrayTestDown->toArray(), $arrayTestMid->toArray(), $arrayTestUp->toArray());
        }

        // return array_merge($arrayTestDown->toArray(), $arrayTestMid->toArray(), $arrayTestUp->toArray());
        // return $arrayTestMid;
    }

    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $indexHour)
    {
        // $this->defineIndexHour($next_day, "$hour");
        // return $this->indexHour;
        //Max cantidad de usuario
        //max cantidad de mesas por reserva

        $timeFoTable                = new TimeForTable;
        $availabilityTables         = collect([]);
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
            return ["hour" => $hour, "tables" => $availabilityTablesIdFinal];
            return ['tables_inicial' => $availabilityTablesId->values()->all(), 'tables final guest' => $availabilityTablesIdFinal->values()->all(), 'tables_indisponibles' => $unavailabilityTablesFilter, 'blocks' => $ListBlocks, 'reservatios' => $ListReservations];
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
        $availabilityTablesFilter = collect([]);
        foreach ($availabilityTables as $tables) {
            foreach ($tables as $table) {
                if ($table['availability'][$indexHour]['rule_id'] >= 2) {
                    $availabilityTablesFilter->push(collect($table));
                    // $availabilityTablesFilter->push(collect($table)->forget('availability'));
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
            return ["group" => $test];
        } else {
            return null;
        }
    }

    public function test($collect, int $num_guests)
    {
        $array = collect([]);
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

    // public function compareTime(string $startTime, string $duration, string $hour, string $durationTimeAux)
    // {
    //     list($h, $m, $s)    = explode(":", $hour);
    //     list($hs, $ms, $ss) = explode(":", $startTime);
    //     list($hd, $md, $sd) = explode(":", $duration);
    //     list($ha, $ma, $sa) = explode(":", $durationTimeAux);

    //     //Hora actual
    //     $hourA = Carbon::createFromTime($h, $m, $s);
    //     //Hora inicial de reservacion
    //     $startTimeR = Carbon::createFromTime($hs, $ms, $ss);
    //     //Hora final de reservacion
    //     $endTimeR = Carbon::createFromTime($hs, $ms, $ss)->addHours($hd)->addMinutes($md)->addSeconds($sd);
    //     //Hora actual aumentado en el rango de duracion promedio de reservacion 01:30:00
    //     $startTimeAuxI = Carbon::createFromTime($h, $m, $s)->addHours($ha)->addMinutes($ma)->addSeconds($sa);

    //     //comparo que la hora actual sea mayor = que la hora inicial de reservación
    //     $mayorRangoMin = $startTimeR->toTimeString() <= $hourA->toTimeString();
    //     //comparo que la hora actual sea menor = que la hora final de reservación
    //     $menorRangoMax = $endTimeR->toTimeString() >= $hourA->toTimeString();
    //     //comparo que la hora inicial aumentado en la hora sea mayor que la hora inicial de reservacion
    //     $mayorRangoMinAux = $startTimeAuxI->toTimeString() > $startTimeR->toTimeString();
    //     //comparo que la hora inicial aumentado en la hora inicial sea menor que la hora final de reservacion
    //     $menorRangoMaxAux = $startTimeAuxI->toTimeString() < $endTimeR->toTimeString();
    //     if ($mayorRangoMin == true && $menorRangoMax == true) {
    //         return false;
    //     } elseif ($mayorRangoMinAux == true && $menorRangoMaxAux == true) {
    //         return false;
    //     } else {
    //         return true;
    //     }
    // }
}
