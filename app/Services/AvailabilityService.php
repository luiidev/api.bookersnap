<?php

namespace App\Services;

use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\Entities\ev_event;
use App\Entities\Reservation;
use App\Entities\res_table_reservation_temp;
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
    private $max_people_standing;
    private $status_standing    = 1;
    private $id_res_source_type = 4; //Reservación tipo WEB
    private $id_status_reserved = 1; //Reservado
    private $id_status_released = 5; //Liberada
    private $id_status_cancel   = 6; //Cancelada
    private $id_status_absent   = 7; //Ausente
    private $id_event_payment   = 2;
    private $id_event_free      = 1;
    private $id_promotion       = 3;
    private $durationTimeAux    = "01:30:00";

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, ConfigurationService $ConfigurationService, TimeForTable $TimeForTable)
    {
        $this->calendarService      = $CalendarService;
        $this->turnService          = $TurnService;
        $this->configurationService = $ConfigurationService;
        $this->timeForTable         = $TimeForTable;
    }

    public function getHours(int $microsite_id, string $date, $zone_id, string $timezone)
    {
        $now                     = Carbon::now($timezone);
        $timeNow                 = $this->dateMaxFormat($now);
        $availabilityTablesInit  = collect($this->searchTablesReservation($date, $microsite_id, $zone_id));
        $itemAux['nextDayClose'] = $availabilityTablesInit['day'];
        $itemAux['hourClose']    = $availabilityTablesInit['hourClose'];
        $itemAux['dayClose']     = $availabilityTablesInit['dayClose'];

        return $itemAux;
    }

    public function searchZones(int $microsite_id, string $date)
    {

        $zonesId  = $this->calendarService->getZones($microsite_id, $date, $date);
        $zoneNull = collect(["id" => null, "name" => "TODOS"]);
        $zonesId->prepend($zoneNull);
        return $zonesId->map(function ($item, $key) {
            $itemAux           = [];
            $itemAux['id']     = $item['id'];
            $itemAux['option'] = $item['name'];
            return $itemAux;
        });
    }

    public function searchAvailabilityDayAllZone(int $microsite_id, string $date, string $hour, int $num_guests, int $next_day, string $timezone)
    {

        $zonesId  = $this->calendarService->getZones($microsite_id, $date, $date)->pluck('id');
        $response = collect();
        foreach ($zonesId as $index => $zoneId) {
            $zoneAvailability = $this->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zoneId, $next_day, $timezone);

            if ($zoneAvailability->count() !== 1) {
                $auxZone = $zoneAvailability->map(function ($item) use ($zoneId) {
                    $item["zone_id"] = $zoneId;
                    return $item;
                });
                foreach ($auxZone as $availability) {
                    if (isset($availability["tables_id"])) {
                        if (count($availability["tables_id"]) > 0) {
                            return $auxZone;
                        }
                    }
                }
            } elseif ($zoneAvailability->count() == 1) {
                return $zoneAvailability;
            }
        }
        //Si no encuentra disponibilidad en ningun micrositio
        return $this->setAvailabilityNull($date, $hour, $timezone, $next_day);
    }

    private function setAvailabilityNull(string $date, string $hour, string $timezone, int $next_day)
    {
        $arrayMid  = collect();
        $hourQuery = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $timezone)->addDay($next_day);
        $init      = $this->defineIndexHour($next_day, $hourQuery->toTimeString());
        $arrayMid->push(["hour" => $hourQuery->toTimeString(), "tables_id" => null, "ev_event_id" => null]);
        $arrayUp   = collect();
        $arrayUp   = $this->addUpAvailavility($arrayUp, $init + 1, 120, null);
        $arrayDown = collect();
        $arrayDown = $this->addDownAvailavility($arrayDown, $init - 1, 0, null);

        $auxCollect = collect(array_merge($arrayDown->toArray(), $arrayMid->toArray(), $arrayUp->toArray()));
        return $auxCollect->map(function ($item) {
            $item["zone_id"] = null;
            return $item;
        });
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
        $this->max_people_standing = $configuration->max_people_standing;

        $this->checkReservationTimeTolerance($date, $this->time_tolerance, $microsite_id, $timezone);

        if ($this->maxPeople < $num_guests) {
            abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        }

        $today    = Carbon::today($timezone);
        $tomorrow = $today->copy()->addDay();

        $availabilityTablesInit = $this->searchTablesReservation($date, $microsite_id, $zone_id);
        if ($availabilityTablesInit['availability']->count() > 0) {
            $dateCloseInit = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesInit['dayClose'] . " " . $availabilityTablesInit['hourClose'], $timezone)->addDay($availabilityTablesInit['day']);
            $event         = $this->checkEventPayment($date, $microsite_id, $hour, $dateCloseInit, $this->id_event_payment, $next_day, $timezone);
        } else {
            $event = collect(["event" => null, "hourMin" => null, "hourMax" => null]);
        }

        if ($event->get("event") !== null) {
            return collect(["ev_env_id" => $event->get('event')['id']]);
        } else {
            $searchPromotions = false;
            if ($event->get('hourMax') == null && $event->get('hourMin') == null) {
                $availabilityTablesEvents = $this->searchTablesEventFree($today, $tomorrow, $microsite_id, $zone_id);
                $availabilityTablesNormal = $availabilityTablesInit;
                if ($availabilityTablesNormal['availability']->count() > 0 && $availabilityTablesEvents['availability']->count() > 0) {
                    $availabilityTables = $this->algoritmoTables($date, $timezone, $availabilityTablesEvents, $availabilityTablesNormal);
                    $dateCloseNormal    = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesNormal['dayClose'] . " " . $availabilityTablesNormal['hourClose'], $timezone)->addDay($availabilityTablesNormal['day']);
                    $dateCloseEvent     = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesEvents['dayClose'] . " " . $availabilityTablesEvents['hourClose'], $timezone)->addDay($availabilityTablesEvents['day']);
                    if ($dateCloseNormal->toDateTimeString() > $dateCloseEvent->toDateTimeString()) {
                        $dateClose  = $dateCloseNormal;
                        $indexClose = $this->defineIndexHour($availabilityTablesNormal['day'], $dateClose->toTimeString());
                    } else {
                        $dateClose  = $dateCloseEvent;
                        $indexClose = $this->defineIndexHour($availabilityTablesEvents['day'], $dateClose->toTimeString());
                    }
                } elseif ($availabilityTablesEvents['availability']->count() > 0) {
                    $availabilityTables = $availabilityTablesEvents;
                    $dateClose          = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesEvents['dayClose'] . " " . $availabilityTablesEvents['hourClose'], $timezone)->addDay($availabilityTablesEvents['day']);
                    $indexClose         = $this->defineIndexHour($availabilityTablesEvents['day'], $dateClose->toTimeString());
                } elseif ($availabilityTablesNormal['availability']->count() > 0) {
                    $searchPromotions               = true;
                    $availabilityTables             = $availabilityTablesNormal;
                    $availabilityTables['event_id'] = null;
                    $dateClose                      = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesNormal['dayClose'] . " " . $availabilityTablesNormal['hourClose'], $timezone)->addDay($availabilityTablesNormal['day']);
                    $indexClose                     = $this->defineIndexHour($availabilityTablesNormal['day'], $dateClose->toTimeString());
                } else {
                    return "No hay disponibilidad";
                }
            } else {
                $searchPromotions               = true;
                $availabilityTables             = $availabilityTablesInit;
                $availabilityTables['event_id'] = null;
                $dateClose                      = $dateCloseInit;
                $indexClose                     = $this->defineIndexHour($availabilityTables['day'], $dateClose->toTimeString());
            }
            $hours            = $this->formatActualHour($date, $hour, $timezone, $next_day);
            $hourQuery        = $hours->get("hourQuery");
            $hourAvailability = $hours->get("hourAvailability");
            $hourUp           = $hours->get("hourUp");
            $hourDown         = $hours->get("hourDown");
            $indexQuery       = $this->defineIndexHour($next_day, $hourQuery->toTimeString());
            if ($hourQuery->toDateTimeString() > $hourAvailability->toDateTimeString()) {
                $indexAvailability = $this->defineIndexHour(0, $hourAvailability->toTimeString());
            } else {
                $indexAvailability = $this->defineIndexHour($next_day, $hourAvailability->toTimeString());
            }
            if ($hourUp->toDateString() > $hourQuery->toDateString()) {
                $indexHourInitUp = $this->defineIndexHour(1, $hourUp->toTimeString());
            } else {
                $indexHourInitUp = $this->defineIndexHour($next_day, $hourUp->toTimeString());
            }
            if ($hourDown->toDateString() < $hourQuery->toDateString()) {
                $indexHourInitDown = $this->defineIndexHour(0, $hourDown->toTimeString());
            } else {
                $indexHourInitDown = $this->defineIndexHour($next_day, $hourDown->toTimeString());
            }
            if ($event->get("hourMax") !== null && $event->get("hourMin") !== null) {
                if ($event->get('hourMin')->toDateString() < $event->get('hourMax')->toDateString()) {
                    $indexHourMax = $this->defineIndexHour(1, $event->get('hourMax')->toTimeString());
                    $indexHourMin = $this->defineIndexHour(0, $event->get('hourMin')->toTimeString());
                } else {
                    if ($hourQuery->toDateString() > $event->get('hourMax')->toDateString()) {
                        $indexHourMax = $this->defineIndexHour(0, $event->get('hourMax')->toTimeString());
                        $indexHourMin = $this->defineIndexHour(0, $event->get('hourMin')->toTimeString());
                    } else {
                        $indexHourMax = $this->defineIndexHour($next_day, $event->get('hourMax')->toTimeString());
                        $indexHourMin = $this->defineIndexHour($next_day, $event->get('hourMin')->toTimeString());
                    }
                }
                if ($next_day == 0) {

                    if ($searchPromotions == true) {
                        $availabilitySinPromociones = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexAvailability, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                        $aux                        = collect();
                        foreach ($availabilitySinPromociones as $availability) {
                            if (count($availability['tables_id']) > 0) {
                                //Buscar promociones se tiene $date & $hour
                                //Servicio para buscar las promociones
                                $promotionsId                = $this->searchPromotions($date, $next_day, $availability['hour'], $microsite_id, $timezone);
                                $availability['ev_event_id'] = $promotionsId;
                            }
                            $aux->push($availability);
                        }
                        return $aux;
                    } else if ($searchPromotions == false) {

                        return $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexAvailability, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                    }

                } else {
                    return $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMax, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                }

            } else {
                $indexHourMax = $indexClose;
                $indexHourMin = $indexAvailability;
                if ($searchPromotions == true) {
                    $availabilitySinPromociones = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMin, $indexHourMax, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                    $aux                        = collect();
                    foreach ($availabilitySinPromociones as $availability) {
                        if (count($availability['tables_id']) > 0) {

                            //Buscar promociones se tiene $date & $hour
                            //Servicio para buscar las promociones
                            $promotionsId = $this->searchPromotions($date, $next_day, $availability['hour'], $microsite_id, $timezone);
                            if ($promotionsId->count() > 0) {
                                $availability['ev_event_id'] = $promotionsId;

                            } else {
                                $availability['ev_event_id'] = null;
                            }
                        }
                        $aux->push($availability);
                    }
                    return $aux;

                } else if ($searchPromotions == false) {
                    $availabilityEventsFree = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMin, $indexHourMax, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                    $aux                    = collect();
                    // return "test";
                    foreach ($availabilityEventsFree as $availabilityFree) {

                        // Buscar el evento $availabilityFree['ev_event_id'] y retornar su date time
                        // return $availabilityFree['hour'];
                        if (isset($availabilityFree['hour']) && isset($availabilityFree['tables_id'])) {

                            //Buscar fecha de inicio del evento
                            $eventFree     = ev_event::find($availabilityFree['ev_event_id']);
                            $eventInitTime = $eventFree->datetime_event;

                            if ($next_day == 1) {
                                $date = Carbon::createFromFormat('Y-m-d', $date, $timezone)->addDay()->toDateString();
                            }
                            $dateHour = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $availabilityFree['hour'], $timezone)->toDateTimeString();
                            if ($dateHour < $eventInitTime) {

                                //Buscar promociones se tiene $date & $hour
                                //Servicio para buscar las promociones
                                $promotionsId = $this->searchPromotions($date, $next_day, $availabilityFree['hour'], $microsite_id, $timezone);
                                if ($promotionsId->count()) {
                                    $availabilityFree['ev_event_id'] = $promotionsId;
                                } else {
                                    $availabilityFree['ev_event_id'] = null;
                                }
                            }
                        }
                        $aux->push($availabilityFree);
                    }
                    return $aux;
                    // return $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMin, $indexHourMax, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $availabilityTables['event_id']);
                }
            }

        }
    }

    public function searchAvailavilityFormat(int $indexQuery, int $indexAvailability, int $indexHourInitDown, int $indexHourInitUp, int $indexHourMin, int $indexHourMax, int $microsite_id, string $date, Carbon $hourQuery, int $num_guests, int $zone_id, string $timezone, $availabilityTables, $eventId)
    {
        $arrayMid   = collect();
        $resultsMid = [];
        if ($indexQuery < $indexAvailability) {
            $indexQuery      = $indexAvailability;
            $indexHourInitUp = $indexAvailability;
        } else {
            $resultsMid = $this->getAvailabilityBasic($microsite_id, $date, $hourQuery->toTimeString(), $num_guests, $zone_id, $indexQuery, $timezone, $availabilityTables, $eventId);
            if ($indexQuery - $indexAvailability <= 2 && $indexQuery - $indexAvailability > 0) {
                $indexHourMin--;
            }
        }
        // dd("test");
        if (count($resultsMid) > 0) {
            $arrayMid->push($resultsMid);
        } else {
            $arrayMid->push(["hour" => $hourQuery->toTimeString(), "tables_id" => null, "ev_event_id" => null]);
        }

        $arrayUp = $this->searchUpAvailability($indexHourInitUp, $microsite_id, $date, $num_guests, $zone_id, $indexHourMax, $timezone, $availabilityTables, $eventId);

        $arrayDown = $this->searchDownAvailability($indexHourInitDown, $microsite_id, $date, $num_guests, $zone_id, $indexHourMin, $timezone, $availabilityTables, $eventId);

        $cantUp = $arrayUp->count();
        if ($cantUp < 2) {
            $arrayUp = $this->addUpAvailavility($arrayUp, $indexHourInitUp + $cantUp, $indexHourMax, $eventId);
        }
        $cantDown = $arrayDown->count();
        if ($cantDown < 2) {
            $arrayDown = $this->addDownAvailavility($arrayDown, $indexHourInitDown - $cantDown, $indexHourMin, $eventId);
        }
        // dd("test");
        return array_merge($arrayDown->toArray(), $arrayMid->toArray(), $arrayUp->toArray());
    }
/**
 * permite calcula la fecha actual en el formato de multiplo de 15 minutos y devolverte la fecha actual, fecha permitida de busqueda asi como la hora busqueda superio
 * @param  string $hour     hora de busqueda
 * @param  string $timezone timezone del micrositio
 * @param  int    $next_day valor de 0 y 1
 * @return array           devuelve un array con la hora de busqueda, hora actual y hora de busqueda superior inicial
 */
    public function formatActualHour(string $date, string $hourQuery, string $timezone, int $next_day)
    {

        $hourQuery        = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hourQuery, $timezone)->addDay($next_day);
        $hourQueryAux     = $hourQuery->copy();
        $hourAvailability = $this->dateMaxFormat(Carbon::now($timezone));
        if ($hourQueryAux <= $hourAvailability) {
            $hourQueryAux = $hourAvailability->copy();
            $hourInitDown = $hourQueryAux->copy();
        } else {
            $hourQueryAux = $hourQuery->copy();
            $hourInitDown = $hourQueryAux->copy()->subMinutes(15);
        }
        $hourInitUp = $hourQueryAux->copy()->addMinutes(15);
        return collect(["hourQuery" => $hourQuery, "hourAvailability" => $hourAvailability, "hourUp" => $hourInitUp, "hourDown" => $hourInitDown]);
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
    public function searchUpAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id, int $indexHourMax, string $timezone, $availabilityTables, $eventId)
    {
        $arrayUp     = collect();
        $indexUpHour = $indexHourInit;
        while ($indexUpHour < $indexHourMax) {
            $resultsUp = $this->getAvailabilityBasic($microsite_id, $date, $this->timeForTable->indexToTime($indexUpHour), $num_guests, $zone_id, $indexUpHour, $timezone, $availabilityTables, $eventId);
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
    public function searchDownAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id, int $indexHourMin, string $timezone, $availabilityTables, $eventId)
    {

        $arrayDown     = collect();
        $indexDownHour = $indexHourInit;
        while ($indexDownHour > $indexHourMin) {
            $resultsDown = $this->getAvailabilityBasic($microsite_id, $date, $this->timeForTable->indexToTime($indexDownHour), $num_guests, $zone_id, $indexDownHour, $timezone, $availabilityTables, $eventId);
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
    public function addUpAvailavility($arrayUp, int $indexHourInit, int $indexHourMax, $eventId)
    {
        $countUp    = $arrayUp->count();
        $indexUpAux = $indexHourInit;
        for ($i = $countUp; $i < 2; $i++) {
            if ($indexUpAux < $indexHourMax) {
                $arrayUp->push(["hour" => $this->timeForTable->indexToTime($indexUpAux), "tables_id" => null, "ev_event_id" => null]);
                $indexUpAux++;
            } else {
                $arrayUp->push(["hour" => null, "tables_id" => null, "ev_event_id" => null]);
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
    public function addDownAvailavility($arrayDown, int $indexHourInit, int $indexHourActualAux, $eventId)
    {
        $countDown    = $arrayDown->count();
        $indexDownAux = $indexHourInit - 1;
        for ($i = $countDown; $i < 2; $i++) {
            if ($indexDownAux >= $indexHourActualAux) {
                $arrayDown->prepend(["hour" => $this->timeForTable->indexToTime($indexDownAux), "tables_id" => null, "ev_event_id" => null]);
                $indexDownAux--;
            } else {
                $arrayDown->prepend(["hour" => null, "tables_id" => null, "ev_event_id" => null]);
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
    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $indexHour, string $timezone, $availabilityTables, $eventId)
// public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day)
    {
        // return $availabilityTables;
        $timeFoTable                = new TimeForTable;
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $availabilityTablesId       = [];
        $availabilityTablesIdFinal  = [];

        list($year, $month, $day) = explode("-", $date);
        list($h, $m, $s)          = explode(":", $hour);
        list($hd, $md, $sd)       = explode(":", $this->durationTimeAux);
        $startHour                = Carbon::create($year, $month, $day, $h, $m, $s, $timezone);
        $endHour                  = Carbon::create($year, $month, $day, $h, $m, $s, $timezone)->addHours($hd)->addMinutes($md)->addSeconds($sd);

        //Buscar las mesas disponibles en los turnos filtrados
        //Devuelve las mesas filtradas por el tipo de reservacion
        $availabilityTablesFilter = $this->getFilterTablesGuest($availabilityTables, $indexHour);
        if ($availabilityTablesFilter->isEmpty()) {
            return [];
        }
        //Devulve los id de las mesas que fueron filtradas por tipo de reservacion y numero de invitados
        $availabilityTablesId = $availabilityTablesFilter->pluck('id');

        //Devuelve id de las mesas filtradas que estan bloquedadas en una fecha y hora
        $listBlocks = $this->getTableBlock($availabilityTablesId->toArray(), $date, $startHour->toDateTimeString(), $endHour->toDateTimeString());

        //Devuelve id de las mesas filtradas que estan reservadas en una fecha y hora
        $listReservations = $this->getTableReservation($availabilityTablesId->toArray(), $date, $startHour->toDateTimeString(), $endHour->toDateTimeString());

        $listReservationsTemp = $this->getReservationTemp($availabilityTablesId->toArray(), $date, $startHour->toDateTimeString(), $timezone, $microsite_id);

        $unavailabilityTablesFilter = collect(array_merge($listBlocks, $listReservations, $listReservationsTemp))->unique();

        $availabilityTablesId = $availabilityTablesId->diff($unavailabilityTablesFilter);

        //Filtrar de las mesas disponibles la cantidad de usuarios
        $availabilityTablesIdFinal = $this->availabilityTablesIdFinal($availabilityTablesId->toArray(), $num_guests);

        if ($availabilityTablesIdFinal->count() > 0) {
            // $event = $this->checkEventFree($date, $microsite_id, $hour, 1);
            return ["hour" => $hour, "tables_id" => [$availabilityTablesIdFinal->first()], "ev_event_id" => $eventId];

        } else {
            $availabilityTablesIdFinal = $this->algoritmoAvailability($availabilityTablesId->toArray(), $num_guests);
            // return ["hour" => $hour, "tables_id" => $availabilityTablesIdFinal];
            if (isset($availabilityTablesIdFinal)) {
                // $event->$this->checkEventFree($date, $microsite_id, $hour, 1);
                return ["hour" => $hour, "tables_id" => $availabilityTablesIdFinal, "ev_event_id" => $eventId];
            } else {
                $availabilityTablesIdFinal = $this->checkReservationStandingPeople($date, $this->time_tolerance, $timezone, $microsite_id, $num_guests);

                // $event->$this->checkEventFree($date, $microsite_id, $hour, 1);
                return ["hour" => $hour, "tables_id" => $availabilityTablesIdFinal, "ev_event_id" => $eventId];
            }
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
                ->where('res_reservation_status_id', '<>', $this->id_status_released)
                ->where('res_reservation_status_id', '<>', $this->id_status_cancel)
                ->where('res_reservation_status_id', '<>', $this->id_status_absent)
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
        // return $combination;
        if (isset($combination)) {
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
                        // return [$array->pluck('id'), $num_guests];
                    } else {
                        // return [$array->pluck('id'), $num_guests];
                        return null;
                    }
                }

            } else {
                $array->push($table);
                $num_guests = $num_guests - $table->max_cover;
            }
        }
        // return [$array->pluck('id'), "standing" => $num_guests];
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
 * @param  int    $microsite_id    id del micrositio
 * @param  string $timezone       time zone del micrositio
 * @return boolean                 Si se realiza algun cambio devuelve true caso contratio falso
 */
    public function checkReservationTimeTolerance(string $date, string $time_tolerance, int $microsite_id, string $timezone)
    {
        if ($time_tolerance !== 0) {
            $time_tolerance_string = Carbon::createFromTime(0, 0, 0, $timezone)->addMinutes($time_tolerance)->toTimeString();
            $dateActual            = Carbon::now($timezone);
            $hourActual            = $dateActual->toDateTimeString();
            $reservations          = Reservation::where("ms_microsite_id", $microsite_id)
                ->where("date_reservation", $date)
                ->where("res_source_type_id", $this->id_res_source_type)
                ->where('res_reservation_status_id', $this->id_status_reserved)
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

    public function checkEventPayment(string $date, int $microsite_id, string $hour, Carbon $dateClose, int $type_event_id, int $next_day, string $timezone)
    {
        $dateC = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $timezone);
        if ($next_day == 1) {
            $dateC->addDay();
        }
        $today = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . "00:00:00", $timezone);
        $final = $dateClose;
        $event = ev_event::where('ms_microsite_id', $microsite_id)
            ->where('bs_type_event_id', $type_event_id) //1:eventogratuito 2:eventopaga 3:promocion gratis 4 promocion de paga
            ->where('datetime_event', '>=', $today->toDateTimeString())
            ->where('datetime_event', '<=', $final->toDateTimeString())
            ->first();
        // return ["Abrir" => $today->toDateTimeString(), "Cierre" => $final->toDateTimeString(), "Inicio Evento" => $event->datetime_event, "Fecha Consulta" => $dateC->toDateTimeString()];
        if (isset($event)) {
            $dateEvent = $this->dateMinFormat(Carbon::createFromFormat('Y-m-d H:i:s', $event->datetime_event, $timezone));
            if ($dateEvent->toDateTimeString() <= $dateC->toDateTimeString() && $dateC->toDateTimeString() <= $final->toDateTimeString()) {
                return collect(["event" => $event, "hourMin" => $dateEvent, "hourMax" => $final]);
            } else {
                return collect(["event" => null, "hourMin" => $dateEvent, "hourMax" => $final]);
            }
            // $dateEvent = $this->dateMinFormat(Carbon::createFromFormat('Y-m-d H:i:s', $event->datetime_event, $timezone));
            // if ($dateEvent->toDateTimeString() > $dateC->toDateTimeString()) {
            //     return collect(["event" => $event, "hourMin" => $dateEvent, "hourMax" => $final]);
            // } elseif ($dateC->toDateTimeString() <= $final->toDateTimeString()) {
            //     return collect(["event" => $event, "hourMin" => $dateEvent, "hourMax" => $final]);
            // } else {
            //     return collect(["event" => $event, "hourMin" => $dateEvent, "hourMax" => $final]);
            // }
        } else {
            return collect(["event" => null, "hourMin" => null, "hourMax" => null]);
        }
    }

    public function checkEventFree(string $date, int $microsite_id, string $hour, int $type_event_id)
    {

    }

    private function dateMinFormat(Carbon $dateTime)
    {
        $date         = $dateTime;
        $date->second = 0;
        if ($date->minute < 15) {
            $date->minute = 0;
        } elseif ($date->minute < 30) {
            $date->minute = 15;
        } elseif ($date->minute < 45) {
            $date->minute = 30;
        } else {
            $date->minute = 45;
        }
        return $date;
    }

    private function dateMaxFormat(Carbon $dateTime)
    {
        $date         = $dateTime;
        $date->second = 0;
        if ($date->minute < 15) {
            $date->minute = 15;
        } elseif ($date->minute < 30) {
            $date->minute = 30;
        } elseif ($date->minute < 45) {
            $date->minute = 45;
        } else {
            $date->addHour();
            $date->minute = 0;
        }
        return $date;
    }

    public function checkReservationStandingPeople(string $date, string $time_tolerance, string $timezone, int $microsite_id, int $num_guests)
    {
        $dateActual = Carbon::now($timezone);
        $hourActual = $dateActual->toDateTimeString();
        if ($time_tolerance !== 0) {
            $time_tolerance_string = Carbon::createFromTime(0, 0, 0, $timezone)->addMinutes($time_tolerance)->toTimeString();
            $reservations          = Reservation::selectRaw("count(num_guest) as num_guests_standing")->where('ms_microsite_id', $microsite_id)
                ->where('date_reservation', $date)
                ->where('status_standing', $this->status_standing)
                ->where('res_source_type_id', $this->id_res_source_type)
                ->where('res_reservation_status_id', $this->id_status_reserved)
                ->whereRaw("addtime(concat(date_reservation,' ',hours_reservation),?) <= ?", array($time_tolerance_string, $hourActual))
                ->first();
        } else {
            $reservations = Reservation::selectRaw("count(num_guest) as num_guests_standing")->where('ms_microsite_id', $microsite_id)
                ->where('date_reservation', $date)
                ->where('status_standing', $this->status_standing)
                ->where('res_source_type_id', $this->id_res_source_type)
                ->where('res_reservation_status_id', $this->id_status_reserved)
                ->whereRaw("concat(date_reservation,' ',hours_reservation) <= ?", array($hourActual))
                ->first();
        }
        $cantGuest = $num_guests + $reservations->num_guests_standing;
        if ($num_guests + $reservations->num_guests_standing <= $this->max_people_standing) {
            return collect(["availability_standing" => true, "num_guest_availability" => $cantGuest, "num_guest_s_max" => $this->max_people_standing]);
        } else {
            return collect();
        }
    }

    public function searchTablesEventFree(Carbon $today, Carbon $tomorrow, int $microsite_id, int $zone_id)
    {
        $availabilityTablesEvents = collect();
        $turn                     = collect();
        $eventsFree               = ev_event::with("turn")
            ->where('datetime_event', '>=', $today->toDateTimeString())
            ->where('datetime_event', '<', $tomorrow->toDateTimeString())
            ->where('bs_type_event_id', $this->id_event_free)
            ->where('ms_microsite_id', $microsite_id)
            ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
            ->get();
        if ($eventsFree->count() > 0) {
            foreach ($eventsFree as $eventFree) {
                $dayCloseEvent  = $today->toDateString();
                $hourCloseEvent = $eventFree['turn']['hours_end'];
                $day            = $eventFree['turn']['hours_ini'] > $eventFree['turn']['hours_end'] ? 1 : 0;
                $turn->push($eventFree['turn']);
                $availabilityTablesEvents->push($this->turnService->getListTable($eventFree['turn']['id'], $zone_id));
            };
            return ['availability' => $availabilityTablesEvents, 'dayClose' => $dayCloseEvent, 'hourClose' => $hourCloseEvent, 'day' => $day, 'eventFree' => $eventsFree, 'turn' => $turn];
        } else {
            return ['availability' => collect(), 'dayClose' => null, 'hourClose' => null, 'day' => null, 'eventFree' => null, 'turn' => null];
        }
    }

    public function searchTablesReservation(string $date, int $microsite_id, int $zone_id)
    {
        $availabilityTables = collect();
        $turnHour           = collect();
        list($y, $m, $d)    = explode("-", $date);
        $turnsFilter        = $this->calendarService->getList($microsite_id, $y, $m, $d);
        foreach ($turnsFilter as $turn) {
            $dayClose  = $turn['end_date'];
            $hourClose = $turn['turn']['hours_end'];
            $day       = $turn['turn']['hours_ini'] > $turn['turn']['hours_end'] ? 1 : 0;
            $turnHour->push($turn['turn']);
            $availabilityTables->push($this->turnService->getListTable($turn['turn']['id'], $zone_id));
        };
        return ['availability' => $availabilityTables, 'dayClose' => $dayClose, 'hourClose' => $hourClose, 'day' => $day, 'turn' => $turnHour];
    }

    public function algoritmoTables(string $date, string $timezone, $availabilityTablesEvents, $availabilityTablesNormal)
    {
        $dataRange           = $this->rangeEvent($date, $timezone, $availabilityTablesEvents, $availabilityTablesNormal);
        $eventId             = $availabilityTablesEvents['eventFree']->first()->id;
        $turnEvent           = $dataRange['event']->first();
        $turnEvent['tables'] = $turnEvent['tables']->first();
        $turnNormal          = $dataRange['normal']->push($turnEvent);
        $turnRange           = $dataRange['range'];
        $initE               = $dataRange['initE'];
        $finE                = $dataRange['finE'];

        $availabilityTurn         = collect();
        $availabilityAvailability = collect();
        foreach ($turnRange as $range) {
            $normal = $turnNormal->where('id', $range['id'])->first();
            if (isset($normal)) {
                $availavilityAux = collect();
                foreach ($normal['tables'] as $tableNormal) {
                    $tableEvent = $turnEvent['tables']->where('id', $tableNormal['id'])->first();
                    if (isset($tableEvent)) {
                        $auxN = $tableNormal['availability'];
                        for ($i = $range['indexHourInit']; $i <= $range['indexHourFin']; $i++) {
                            if ($tableEvent['availability'][$i]['rule_id'] == 2 && $i <= $finE) {
                                $auxN[$i]['rule_id']  = 2;
                                $auxN[$i]['event']    = true;
                                $auxN[$i]['event_id'] = $eventId;
                            } else if ($tableEvent['availability'][$i]['rule_id'] !== 2 && $i <= $finE) {
                                $auxN[$i]['rule_id']  = -1;
                                $auxN[$i]['event']    = true;
                                $auxN[$i]['event_id'] = $eventId;
                            } else {
                                $auxN[$i]['rule_id']  = $tableNormal['availability'][$i]['rule_id'];
                                $auxN[$i]['event']    = false;
                                $auxN[$i]['event_id'] = null;
                            }
                        }
                        $tableNormal['availability'] = $auxN;
                        $availavilityAux->push($tableNormal);
                    }
                }
                $normalAux1 = $normal;
                $normalAux2 = $availavilityAux;
                $availabilityTurn->push($normalAux1);
                $availabilityAvailability->push($normalAux2);
            }
        }
        $finalFormat                 = [];
        $finalFormat['availability'] = $availabilityAvailability;
        $finalFormat['turn']         = $availabilityTurn;

        $finalFormat['event_id'] = $eventId;
        if ($availabilityTablesEvents['day'] > $availabilityTablesNormal['day']) {
            $finalFormat['day'] = $availabilityTablesEvents['day'];
        } else {
            $finalFormat['day'] = $availabilityTablesNormal['day'];
        }
        return $finalFormat;
    }

    private function rangeEvent(string $date, string $timezone, $availabilityTablesEvents, $availabilityTablesNormal)
    {
        $turnNewEvent        = collect();
        $turnEvent           = $availabilityTablesEvents['turn']->first();
        $dayEvent            = $turnEvent->hours_ini > $turnEvent->hours_end ? 1 : 0;
        $hourIni             = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnEvent->hours_ini, $timezone);
        $hourFin             = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnEvent->hours_end, $timezone)->addDay($dayEvent);
        $dayEventIni         = $hourIni->toDateString() < $hourFin->toDateString() ? 0 : $dayEvent;
        $init                = $this->defineIndexHour($dayEventIni, $hourIni->toTimeString());
        $fin                 = $this->defineIndexHour($dayEvent, $hourFin->toTimeString());
        $turnEvent['tables'] = $availabilityTablesEvents['availability'];
        $turnNewEvent->push($turnEvent);

        $turnNormalCollection = collect();
        $turnChange           = collect();
        $turnNewNormal        = collect();

        foreach ($availabilityTablesNormal['turn'] as $index => $turnNormal) {
            $turnNormal['tables'] = $availabilityTablesNormal['availability'][$index];
            $turnNewNormal->push($turnNormal);
        };

        foreach ($availabilityTablesNormal['turn'] as $indexAux => $turnNormal) {
            $dayNormal     = $turnNormal['hours_ini'] > $turnNormal['hours_end'] ? 1 : 0;
            $hourIniNormal = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnNormal['hours_ini'], $timezone);
            $hourFinNormal = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnNormal['hours_end'], $timezone)->addDay($dayNormal);
            $dayNormalIni  = $hourIniNormal->toDateString() < $hourFinNormal->toDateString() ? 0 : $dayNormal;
            $indexHourInit = $this->defineIndexHour($dayNormalIni, $hourIniNormal->toTimeString());
            $indexHourFin  = $this->defineIndexHour($dayNormal, $hourFinNormal->toTimeString());
            if ($hourIni <= $hourIniNormal && $hourFin <= $hourFinNormal) {
                $turnChange->push(["id" => $turnNormal['id'], 'indexHourInit' => $indexHourInit, 'indexHourFin' => $indexHourFin]);
            } elseif ($hourIni <= $hourIniNormal && $hourFin > $hourFinNormal) {
                $turnChange->push(["id" => $turnNormal['id'], 'indexHourInit' => $indexHourInit, 'indexHourFin' => $indexHourFin]);
            } elseif ($hourIni > $hourIniNormal && $hourFin <= $hourFinNormal) {
                $turnChange->push(["id" => $turnNormal['id'], 'indexHourInit' => $indexHourInit, 'indexHourFin' => $indexHourFin]);
            } else if ($hourIni > $hourIniNormal && $hourFin > $hourFinNormal) {
                $turnChange->push(["id" => $turnNormal['id'], 'indexHourInit' => $indexHourInit, 'indexHourFin' => $indexHourFin]);
            }
            if ($indexAux == 0) {
                $auxIni = $indexHourFin + 1;
            } else {
                $auxFin  = $indexHourInit - 1;
                $testing = $turnChange->pop();
                $turnChange->push(["id" => $turnEvent['id'], 'indexHourInit' => $auxIni, 'indexHourFin' => $auxFin]);
                $turnChange->push($testing);
                $auxIni = $indexHourFin + 1;
            }

            $turnNormalCollection->push(["id" => $turnNormal['id'], 'name' => $turnNormal['name'], 'hourIni' => $hourIniNormal, 'hourFin' => $hourFinNormal]);
        }
        $final = $turnChange->pop();
        if ($final['indexHourFin'] < $fin) {
            $final['indexHourFin'] = $fin;
        }
        $turnChange->push($final);
        $inicio = $turnChange->shift();
        if ($inicio['indexHourInit'] > $init) {
            $inicio['indexHourInit'] = $init;
        }
        $turnChange->prepend($inicio);
        return ["event" => $turnNewEvent, "normal" => $turnNewNormal, "range" => $turnChange, "initE" => $init, "finE" => $fin];
    }
    public function searchPromotions(string $date, int $next_day, string $hour, int $microsite_id, string $timezone)
    {
        $dayC       = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $timezone)->addDay($next_day);
        $day        = $dayC->dayOfWeek;
        $promotions = ev_event::where('date_expire', '>=', $date)->where('datetime_event', '<=', $dayC->toDateTimeString())->where('bs_type_event_id', $this->id_promotion)->where('ms_microsite_id', $microsite_id)->with(['turns' => function ($query) use ($hour, $microsite_id) {
            $query->where("hours_ini", '<=', $hour)->where('hours_end', '>=', $hour)->where('bs_type_event_id', $this->id_promotion)->where('ms_microsite_id', $microsite_id)->get();
        }, 'turns.days' => function ($query) use ($day) {
            $query->where('day', $day)->get();
        }])->get();

        $promoaux = collect();
        if ($promotions->count() > 0) {
            foreach ($promotions as $promotion) {
                if ($promotion->turns->count() > 0) {
                    foreach ($promotion->turns as $turn) {
                        if ($turn->days->count() > 0) {
                            foreach ($turn->days as $day) {
                                $promoaux->push($promotion->id);
                                break;
                            }
                        }

                    }
                }

            }
        }
        return $promoaux;
    }

    public function getReservationTemp(array $tables_id, string $date, string $hourI, string $timezone, int $microsite_id)
    {
        $hour                = Carbon::createFromFormat('Y-m-d H:i:s', $hourI, $timezone);
        $hourActual          = Carbon::now($timezone)->subMinutes(10);
        $listReservationTemp = [];
        $tables              = collect();
        $reservations        = res_table_reservation_temp::where('date', $date)->where('hour', $hour->toTimeString())->where('ms_microsite_id', $microsite_id)->get();
        // dd($reservations);
        if ($reservations->count() > 0) {
            $listReservationTemp = $reservations->reject(function ($value) use ($hourActual) {
                return $value->expire < $hourActual->toDateTimeString();
            });
            foreach ($listReservationTemp as $reservationTemp) {
                $tables_id = explode(",", $reservationTemp->tables_id);
                foreach ($tables_id as $id) {
                    $id = (int) $id;
                    $tables->push($id);
                }

            }
            return $tables->toArray();
        } else {
            return $listReservationTemp;
        }

    }
}
