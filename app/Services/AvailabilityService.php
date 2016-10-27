<?php

namespace App\Services;

use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\Entities\Reservation;
use App\res_table;
use App\res_table_reservation;
use App\Services\CalendarService;
use Carbon\Carbon;

class AvailabilityService
{
    private $calendarService;
    private $turnService;
    private $minCombinationTable;
    private $maxPeople;
    private $time_tolerance;
    private $id_status_no_confirm        = 1;
    private $id_res_source_type          = 4;
    private $id_status_cancel_user       = 11;
    private $id_status_cancel_restaurant = 12;
    private $id_status_finish            = 18;
    private $durationTimeAux             = "01:30:00";

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, ConfigurationService $ConfigurationService, TimeForTable $TimeForTable)
    {
        $this->calendarService      = $CalendarService;
        $this->turnService          = $TurnService;
        $this->configurationService = $ConfigurationService;
        $this->timeForTable         = $TimeForTable;
    }

    /**
     * Busca la disponibilidad en un dia y hora determinado valida la hora actual de busqueda y devulve un formato fijo de 5 horarios
     * @param  int    $microsite_id id del micrositio
     * @param  string $date         fecha de la consulta de disponibilidad
     * @param  string $hour         hora de consulta de la disponibilidad
     * @param  int    $num_guests   numero de clientes
     * @param  int    $zone_id      id de la zona de consulta
     * @param  int    $next_day     parametro si es de este dia 0 o del siguiente dia 1
     * @param  string $timezone     time zone del micrositio
     * @return array               array de tamaño 5 con la informacion de disponibilidad
     */
    public function searchAvailabilityDay(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day, string $timezone)
    {
        if ($next_day == 1) {
            if ($hour > "05:45:00") {
                abort(500, "Rango incorrecto los ragos correctos para horario de madrugada son de 00:00:00 a 05:45:00");
            }
        }

        $configuration             = $this->configurationService->getConfiguration($microsite_id);
        $this->minCombinationTable = $configuration->max_table;
        $this->maxPeople           = $configuration->max_people;
        $this->time_tolerance      = $configuration->time_tolerance;
        if ($this->maxPeople < $num_guests) {
            abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        }

        /**
         * Actualiza el estado de las reservaciones de una fecha determinada menores a la fecha actual
         */
        $this->checkReservationTimeTolerance($date, $this->time_tolerance, $microsite_id, $timezone);

        $hourI             = $hour;
        $hours             = $this->formatActualHour($hour, $timezone, $next_day);
        $hour              = $hours->get("hour");
        $indexHourInitI    = $this->defineIndexHour($next_day, $hourI);
        $indexHourInitUp   = $this->defineIndexHour($hours->get("index"), $hours->get("hour2"));
        $indexHourInitDown = $this->defineIndexHour($next_day, $hourI);

        // return ["hourI" => $hourI, "hour" => $hours->get("hour"), "hourA" => $hours->get("hourA"), "hour2" => $hours->get("hour2")];
        $arrayMid   = collect();
        $resultsMid = [];
        if ($hourI === $hour) {
            $resultsMid = $this->getAvailabilityBasic($microsite_id, $date, $hourI, $num_guests, $zone_id, $indexHourInitI);
        }
        if (count($resultsMid) > 0) {
            $arrayMid->push($resultsMid);
        } else {
            $arrayMid->push(["hour" => $hourI, "tables" => null]);
        }

        if ($next_day == 0) {
            $indexHourActualAux = $this->defineIndexHour($next_day, $hours->get("hourA"));
            $arrayUp            = $this->searchUpAvailability($indexHourInitUp, $microsite_id, $date, $num_guests, $zone_id);
            $arrayDown          = $this->searchDownAvailability($indexHourInitDown, $microsite_id, $date, $num_guests, $zone_id, $indexHourActualAux);
            $cantUp             = $arrayUp->count();
            if ($cantUp < 2) {
                $arrayUp = $this->addUpAvailavility($arrayUp, $indexHourInitUp + $cantUp);
            }
            $cantDown = $arrayDown->count();
            if ($cantDown < 2) {
                $arrayDown = $this->addDownAvailavility($arrayDown, $indexHourInitDown - $cantDown, $indexHourActualAux);
            }
        } else {
            $arrayUp   = $this->searchUpAvailability($indexHourInitUp, $microsite_id, $date, $num_guests, $zone_id);
            $arrayDown = $this->searchDownAvailability($indexHourInitI, $microsite_id, $date, $num_guests, $zone_id, 0);
            $cantUp    = $arrayUp->count();
            if ($cantUp < 2) {
                $arrayUp = $this->addUpAvailavility($arrayUp, $indexHourInitUp + $cantUp);
            }
            $cantDown = $arrayDown->count();
            if ($cantDown < 2) {
                $arrayDown = $this->addDownAvailavility($arrayUp, $indexHourInitI - $cantDown, 0);
            }
        }
        return array_merge($arrayDown->toArray(), $arrayMid->toArray(), $arrayUp->toArray());
    }

    /**
     * permite calcula la fecha actual en el formato de multiplo de 15 minutos y devolverte la fecha actual, fecha permitida de busqueda asi como la hora busqueda superio
     * @param  string $hour     hora de busqueda
     * @param  string $timezone timezone del micrositio
     * @param  int    $next_day valor de 0 y 1
     * @return array           devuelve un array con la hora de busqueda, hora actual y hora de busqueda superior inicial
     */
    public function formatActualHour(string $hour, string $timezone, int $next_day)
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
        $hourAux = Carbon::parse($hourA);
        if ($hour < $hourAux->toTimeString() && $next_day == 0) {
            $hour = $hourAux->toTimeString();
        }
        list($h, $m, $s) = explode(":", $hour);
        $hourAux2        = Carbon::createFromTime($h, $m, $s, $timezone);
        $hour2           = $hourAux2->addMinutes(15)->toTimeString();
        if ($hour2 > $hour && $next_day != 1) {
            $index = 0;
        } else {
            $index = 1;
        }
        return collect(["hour" => $hour, "hourA" => $hourA->toTimeString(), "hour2" => $hour2, "index" => $index]);
    }

    /**
     * Funcion que busca la disponibilidad superior maximo 2
     * @param  int    $indexHourInit indice de la hora de busqueda inicial superior
     * @param  int    $microsite_id  id del micrositio
     * @param  string $date          fecha de la consulta de disponibilidad
     * @param  int    $num_guests    cantidad de cliente
     * @param  int    $zone_id       id de la zona
     * @return array                devuelve un array de disponibilidad superior, puede devolver vacio si no encuentra nada
     */
    public function searchUpAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id)
    {
        $arrayUp     = collect();
        $indexUpHour = $indexHourInit;
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

    /**
     * Funcion que busca la disponibilidad inferior maximo 2
     * @param  int    $indexHourInit indice de la hora de busqueda inicial inferior
     * @param  int    $microsite_id  id del micrositio
     * @param  string $date          fecha de la consulta de disponibilidad
     * @param  int    $num_guests    cantidad de cliente
     * @param  int    $zone_id       id de la zona
     * @return array                devuelve un array de disponibilidad inferior, puede devolver vacio si no encuentra nada
     */
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

    /**
     * completa la lista de formato superio
     * @param [type] $arrayUp       lista de busqueda superio de disponibilidad
     * @param int    $indexHourInit indice donde se va a empezar a llenar el array
     */
    public function addUpAvailavility($arrayUp, int $indexHourInit)
    {
        $countUp    = $arrayUp->count();
        $indexUpAux = $indexHourInit;
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

    /**
     * completa la lista de formato inferio
     * @param array $arrayDown          lista de busqueda inferior de disponibilidad
     * @param int    $indexHourInit      indice superior para llenar el array
     * @param int    $indexHourActualAux indice inferior limite de llenado del array
     */
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

    /**
     * Busca disponibilidad de una mesa en un hora determinada
     * @param  int    $microsite_id id del microsito a buscar
     * @param  string $date         fecha de la reservacion
     * @param  string $hour         hora de busqueda de la reservacion
     * @param  int    $num_guests   numero de cliente
     * @param  int    $zone_id      zona donde se desea realizar la reservacion
     * @param  int    $indexHour    index de la hora que se desea realizar la busqueda
     * @return array               id de las mesas disponibles para esa fecha y hora determinaada
     */
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
            return ["hour" => $hour, "tables" => [$availabilityTablesIdFinal->first()]];
        } else {
            $availabilityTablesIdFinal = $this->algoritmoAvailability($availabilityTablesId->toArray(), $num_guests);
            return ["hour" => $hour, "tables" => $availabilityTablesIdFinal];
        }

    }

    /**
     * Función que permite determinar las mesas bloqueadas en una hora determinada
     * @param  array  $tables_id lista de mesas disponibles y verifica que si tienen bloqueos
     * @param  string $date      fecha de la reservacion
     * @param  string $hourI     hora inicial de la reservacion
     * @param  string $hourF     hora final de la reservacion
     * @return array            lista de mesas que poseen bloqueos
     */
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
    /**
     * funcion que permite determinar reservaciones en una hora determinada
     * @param  array  $tables_id lista de mesas disponibles y verificar si tienen reservaciones
     * @param  string $date      fecha de la reservacion
     * @param  string $hourI     hora inicial de la reservacion
     * @param  string $hourF     hora final de la reservacion
     * @return array            lista de mesas que poseen reservaciones
     */
    public function getTableReservation(array $tables_id, string $date, string $hourI, string $hourF)
    {
        $listReservation = [];
        $reservations    = res_table_reservation::whereIn('res_table_id', $tables_id)->with(['reservation' => function ($query) use ($date, $hourI, $hourF) {
            $query->where('date_reservation', '=', $date)
                ->where('res_reservation_status_id', '<>', $this->id_status_finish)
                ->where('res_reservation_status_id', '<>', $this->id_status_cancel_user)
                ->where('res_reservation_status_id', '<>', $this->id_status_cancel_restaurant)
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
    /**
     * Funcion que permite filtrar las mesas disponibles en un turno para reservacion en linea estado 2
     * @param  Object $availabilityTables mesas disponibles en un horario determinado
     * @param  int    $indexHour          indice de la hora para validar si en esa hora esta disponible en la web
     * @return object                     conjunto de mesas disponibles para reservar en la web
     */
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

    /**
     * Funcion que verifica que una lista de mesas soporte la cantidad de invitados
     * @param  array  $listId     lista de id de las mesas disponibles
     * @param  int    $num_guests numero de invitados
     * @return array             array de los id de las mesas que soporten la cantidad de invitados
     */
    public function availabilityTablesIdFinal(array $listId, int $num_guests)
    {
        $availabilityNumGuest = res_table::whereIn('id', $listId)
            ->where('min_cover', '<=', $num_guests)
            ->where('max_cover', '>=', $num_guests)->get();
        return $availabilityNumGuest->pluck('id');
    }

    /**
     * busca las mesas cuyo minimo de acepte el numero de usuario apartir de de una lista de id y lo ordena de forma decreciente
     * @param  array  $listId     lista de id de las mesas disponibles
     * @param  int    $num_guests cantidad de invitados
     * @return array             array de lista de combinaciones de mesas, si no existe combinaciones devuelve null
     */
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

    /**
     * Funcion que combina la distribucion de mesas a partir de un numero de cliente
     * @param  Object $collect    coleccion de mesas
     * @param  int    $num_guests numero de cliente
     * @return array             array de id de mesas combinadas, si no se logra realizar la combinacion devuelve null
     */
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

    /**
     * Define el index que le corresponde a partir de la hora y next day, si se supera el limite se asigna un valor fuera del limite 120
     * @param  int $next_day  valor entre 0 y 1, 0 si es del mismo dia y 1 si es del dia siguiente
     * @param  string $hour     hora de consulta
     * @return int           index que se corresponse
     */
    public function defineIndexHour(int $next_day, string $hour)
    {
        if ($next_day == 1) {
            $indexHour      = $this->timeForTable->timeToIndex($hour);
            $indexHourLimit = $this->timeForTable->timeToIndex("06:00:00");
            if ($indexHour >= $indexHourLimit) {
                return 120;
            } else {
                return $indexHour + 96;
            }
        } else {
            return $this->timeForTable->timeToIndex($hour);
        }
    }

    /**
     * Reviza las reservaciones de esa fecha y hora actual y les cambia de estado a estado de cancelado por el restaurante, si el el tiempo de tolerancia es 0 no se realiza ningun cambio
     * @param  string $date           Fecha del dia de busqueda
     * @param  string $time_tolerance tiempo de tolerancia de 0 a 180 minutos 0  es ilimintado
     * @param  int    $microite_id    id del micrositio
     * @param  string $timezone       time zone del micrositio
     * @return boolean                 Si se realiza algun cambio devuelve true caso contratio falso
     */
    public function checkReservationTimeTolerance(string $date, string $time_tolerance, int $microite_id, string $timezone)
    {
        if ($time_tolerance != 0) {
            $time_tolerance_string = Carbon::parse("00:00:00")->addMinutes($time_tolerance)->toTimeString();
            $dateActual            = Carbon::now()->tz($timezone);
            $hourActual            = $dateActual->toDateTimeString();
            $reservations          = Reservation::where("ms_microsite_id", $microite_id)
                ->where("date_reservation", $date)
                ->where("res_source_type_id", $this->id_res_source_type)
                ->where('res_reservation_status_id', $this->id_status_no_confirm)
                ->whereRaw("addtime(concat(date_reservation,' ',hours_reservation),?) <= ?", array($time_tolerance_string, $hourActual))->get();
            if (!$reservations->isEmpty()) {
                foreach ($reservations as $reservation) {
                    $reservation->res_reservation_status_id = 12;
                    $reservation->save();
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}
