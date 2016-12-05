<?php

namespace App\Services;

use App\Domain\Calendar;
use App\Domain\TimeForTable;
use App\Entities\BlockTable;
use App\Entities\ev_event;
use App\Entities\Reservation;
use App\Entities\res_table_reservation_temp;
use App\res_table;
use App\res_table_reservation;
use App\res_turn_calendar;
use App\Services\CalendarService;
use App\Services\Helpers\CalendarHelper;

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
    private $time_restriction;

    public function __construct(CalendarService $CalendarService, TurnService $TurnService, ConfigurationService $ConfigurationService, TimeForTable $TimeForTable)
    {
        $this->calendarService      = $CalendarService;
        $this->turnService          = $TurnService;
        $this->configurationService = $ConfigurationService;
        $this->timeForTable         = $TimeForTable;
    }

    //Retorna los todos los eventos en un dia y hora determinada
    public function getEvents(int $microsite_id, string $date, string $hour, string $timezone, int $next_day, int $zone_id = null)
    {
        if ($next_day == 1) {
            if ($hour > "05:45:00") {
                abort(500, "Rango incorrecto los ragos correctos para horario de madrugada son de 00:00:00 a 05:45:00");
            }
        }
        $zone_id       = isset($zone_id) ? collect($zone_id) : $this->calendarService->getZones($microsite_id, $date, $date)->pluck('id');
        $dateTimeQuery = Carbon::parse($date . " " . $hour, $timezone)->addDay($next_day);
        $today         = Carbon::parse($date, $timezone);
        if (!$zone_id->isEmpty()) {
            foreach ($zone_id as $id) {
                $events = $this->searchAllEvent($microsite_id, $date, $hour, $timezone, $next_day, $id);
                if (!$events->isEmpty()) {
                    break;
                }
            }
            return $events->isEmpty() ? abort(500, "No existe eventos disponibles para " . $dateTimeQuery->formatLocalized('%A %d %B %Y')) : $events;
        } else {
            return abort(500, "No existe zonas disponibles para " . $dateTimeQuery->formatLocalized('%A %d %B %Y'));
        }
    }

    //Retorna todos los eventos en un dia ** Prioridad Eventos de Pago/Eventos Gratuitos/Promociones **
    private function searchAllEvent(int $microsite_id, string $date, string $hour, string $timezone, int $next_day, $zone_id)
    {
        $aTables = $this->searchTablesReservation($date, $microsite_id, $zone_id);
        if (!$aTables['availability']->isEmpty()) {
            $dateTimeClose = Carbon::parse($aTables['dayClose'] . " " . $aTables['hourClose'], $timezone)->addDay($aTables['day']);
            $event         = $this->checkEventPaymentAux($date, $microsite_id, $hour, $dateTimeClose, $next_day, $timezone);
            if ($event->isEmpty()) {
                $eventFree = $this->checkEventFree($date, $microsite_id, $hour, $dateTimeClose, $next_day, $timezone);
                $event     = $this->checkEventPromo($date, $microsite_id, $hour, $dateTimeClose, $next_day, $timezone)->prepend($eventFree);
                if ($event->count() == 1) {
                    return collect();
                }
                if ($eventFree->isEmpty()) {
                    $event->shift();
                }
            }
            return $event;
        } else {
            return collect();
        }
    }

    //Retorna todas las horas disponibles en un dia
    public function getHours(int $microsite_id, string $date, int $zone_id = null, string $timezone)
    {
        $dateC         = Carbon::parse($date, $timezone);
        $configuration = $this->configurationService->getConfiguration($microsite_id);

        $nowC = Carbon::now($timezone)->addMinutes($configuration->time_restriction);
        $diff = $dateC->toDateString() <=> $nowC->toDateString();

        $dateCompare = $dateC->toDateString() <=> $nowC->toDateString();

        $zone_id = isset($zone_id) ? $zone_id : $this->calendarService->getZones($microsite_id, $date, $date)->pluck('id')->toArray();
        $enable  = false;
        if (is_array($zone_id)) {
            foreach ($zone_id as $id) {
                $aTablesE = $this->searchTablesEventFree($dateC, $dateC->copy()->addDay(), $microsite_id, $id);
                $aTablesN = $this->searchTablesReservation($dateC->toDateString(), $microsite_id, $id);
                if ($aTablesN['availability']->count() > 0) {
                    $enable = true;
                    break;
                }
            }
            if (!$enable) {
                return abort(500, "No existe turnos disponibles para " . $dateC->formatLocalized('%A %d %B %Y'));
            }
        } else {
            $aTablesE = $this->searchTablesEventFree($dateC, $dateC->copy()->addDay(), $microsite_id, $zone_id);
            $aTablesN = $this->searchTablesReservation($dateC->toDateString(), $microsite_id, $zone_id);
            if ($aTablesN['availability']->count() == 0) {
                return abort(500, "No existe turnos disponibles para " . $dateC->formatLocalized('%A %d %B %Y'));
            }

        }
        $availabilityTablesInit = $aTablesE['availability']->count() > 0 ? $this->algoritmoTables($date, $timezone, $aTablesE, $aTablesN) : $aTablesN;
        $initAux                = $dateCompare == 0 ? $this->dateMaxFormat($nowC)->toTimeString() : $availabilityTablesInit['hourIni'];

        $limitAux = $availabilityTablesInit['indexLimit']->reject(function ($item) use ($initAux) {
            if ($item['day'] == 0) {
                if (($initAux <=> $item['final']) == 1) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        })->values();

        $limitAux->transform(function ($item) use ($initAux) {
            if (($initAux <=> $item['init']) >= 0 && ($initAux <=> $item['final']) <= 0) {
                $item['init'] = $initAux;
            }
            return $item;
        });

        $auxNext = collect();
        $limitAux->transform(function ($item, $key) use ($auxNext) {
            return $this->timeIndexEvent($item, $key, $auxNext);
        });
        $auxNext;
        if (!$auxNext->isEmpty()) {
            $keyAux   = $auxNext[0]['index'];
            $limitAux = $limitAux->forget($keyAux);
            foreach ($auxNext as $next) {
                $limitAux->splice($keyAux, 0, [$next]);
            }
        }

        $time = collect();
        $limitAux->each(function ($item) use ($time, $dateC, $timezone) {
            $compare = $item['init'] <=> $item['final'];
            if ($compare <= 0) {

            }
            $indexMin = $this->defineIndexHour($item['day'], $item['init']);
            $indexMax = $this->defineIndexHour($item['day'], $item['final']);
            for ($i = $indexMin; $i <= $indexMax; $i++) {
                $timeAux['index']       = $i;
                $timeAux['next_day']    = $i >= 96 ? 1 : 0;
                $timeAux['option']      = $this->timeForTable->indexToTime($i);
                $timeAux['option_user'] = Carbon::createFromFormat('Y-m-d H:i:s', $dateC->toDateString() . " " . $this->timeForTable->indexToTime($i), $timezone)->format('g:i A');
                $timeAux['event']       = null;
                $timeAux['promotions']  = null;
                $time->push($timeAux);
            }
        });

        if ($diff < 0) {
            $hour  = $this->dateMaxFormat($nowC);
            $index = $this->defineIndexHour(1, $hour->toTimeString());
            $time  = $time->reject(function ($item) use ($index) {
                if ($item['index'] >= $index) {
                    return false;
                } else {
                    return true;
                }
            });
        }
        // return $time;

        //Agrega evento gratuito y promociones a las horas disponibles en una fecha determinada
        $events = $this->searchAllEvent($microsite_id, $date, "00:00:00", $timezone, 0, 1);
        if ($events->count() > 0) {
            $indexEvents = $events->map(function ($item) {
                $aux['id']       = $item['id'];
                $aux['type']     = $item['bs_type_event_id'];
                $dayAux          = ($item['hourMax']->toDateString() <=> $item['hourMin']->toDateString()) == 0 ? $item['day'] : 0;
                $aux['indexMin'] = $this->defineIndexHour($dayAux, $item['hourMin']);
                $aux['indexMax'] = $this->defineIndexHour($item['day'], $item['hourMax']);
                return $aux;
            })->sortBy('index')->values();
            $time = $time->map(function ($item) use ($indexEvents) {
                $result             = $this->selectHourEvent($item['index'], $indexEvents);
                $item['event']      = $result['event'];
                $item['promotions'] = count($result['promotions']) > 0 ? $result['promotions'] : null;
                return $item;
            });
        }
        return $time->isEmpty() ? abort(500, "No existe horarios disponibles " . $dateC->formatLocalized('%A %d %B %Y')) : $time;
    }

    public function selectHourEvent($index, $indexEvents)
    {
        $auxP = collect();
        $auxE = collect();
        $indexEvents->each(function ($items) use ($auxP, $auxE, $index) {
            if ($items['indexMin'] <= $index && $index <= $items['indexMax']) {
                if ($items['type'] == 1) {
                    $auxE->push($items['id']);
                    return false;
                } else {
                    $auxP->push($items['id']);
                }
            }
        });
        return ["event" => $auxE->first(), "promotions" => $auxP->toArray()];
    }

    public function timeIndexEvent($item, $key, $aux)
    {
        $compare = $item['init'] <=> $item['final'];
        if ($compare < 0) {
            $item['index'] = $key;
        } elseif ($item['day'] == 1 && $compare > 0) {
            $index['init']  = $item['init'];
            $index['final'] = "23:45:00";
            $index['day']   = 0;
            $index['index'] = $key;
            $aux->prepend($index);
            $index['init']  = "00:00:00";
            $index['final'] = $item['final'];
            $index['day']   = 1;
            $index['index'] = $key;
            $aux->prepend($index);
        }
        return $item;
    }
    //Busca todas las zonas disponibles en un dia
    public function searchZones(int $microsite_id, string $date, string $timezone)
    {
        $zonesId = $this->calendarService->getZones($microsite_id, $date, $date);
        if (!$zonesId->isEmpty()) {
            $zoneNull = collect(["id" => null, "name" => "TODOS"]);
            $zonesId->prepend($zoneNull);
            return $zonesId->map(function ($item, $key) {
                $itemAux           = [];
                $itemAux['id']     = $item['id'];
                $itemAux['option'] = $item['name'];
                return $itemAux;
            });
        } else {
            $dateC = Carbon::parse($date, $timezone);
            abort(500, "No hay zonas disponibles " . $dateC->formatLocalized('%A %d %B %Y'));
        }
    }

    //Busca disponibilidad en todas las zonas
    public function searchAvailabilityDayAllZone(int $microsite_id, string $date, string $hour, int $num_guests, int $next_day, string $timezone, int $eventId = null)
    {
        $this->validNextDate($date, $next_day, $timezone);
        $zonesId  = $this->calendarService->getZones($microsite_id, $date, $date)->pluck('id');
        $response = collect();
        if (!$zonesId->isEmpty()) {
            foreach ($zonesId as $zoneId) {
                $zoneAvailability = $this->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zoneId, $next_day, $timezone, $eventId);
                if ($zoneAvailability->count() !== 1) {
                    $auxZone = $zoneAvailability->map(function ($item) use ($zoneId) {
                        $item['form']["zone_id"] = $zoneId;
                        return $item;
                    });
                    $response->push($auxZone);
                } elseif ($zoneAvailability->count() == 1) {
                    return $zoneAvailability;
                }
            }
            // return $response->collapse()->sortBy('index')->unique('index')->values();
            $index    = $this->defineIndexHour($next_day, $hour);
            $response = $this->selectIndex($response, $index);
            return $response->map(function ($item) {
                if (count($item['form']) == 1) {
                    $item['form'] = null;
                }
                return $item;
            });
        } else {
            $dateC = Carbon::parse($date, $timezone);
            abort(500, "No hay turnos disponibles " . $dateC->formatLocalized('%A %d %B %Y'));
        }
    }

    //Seleccion mesas disponibles de la lista de disponibilidad de todas las zonas
    public function selectIndex($availabilityAllZones, $index)
    {
        $aux             = collect();
        $availabilityAll = $availabilityAllZones->collapse()->sortBy('index');
        $mid             = $availabilityAll->where('index', $index)->reject(function ($item) {
            return !collect($item["tables_id"])->isEmpty() || (@$item["standing_people"] && $item["standing_people"]['availability_standing']) ? false : true;
        });

        $tables = $mid->filter(function ($item, $key) {
            return isset($item['tables_id']);
        })->values();

        if ($tables->isEmpty()) {
            $standing = $mid->filter(function ($item, $key) {
                return (@$item["standing_people"] && $item["standing_people"]['availability_standing']);
            })->values();
            if ($standing->isEmpty()) {
                $aux->push($availabilityAll->where('index', $index)->first());
            } else {
                $aux->push($standing->first());
            }
        } else {
            $aux->push($tables->first());
        }

        $minAll = $availabilityAll->filter()->reject(function ($item) use ($index) {
            return $item['index'] >= $index ? true : false;
        })->sortByDesc('index');

        $maxAll = $availabilityAll->filter()->reject(function ($item) use ($index) {
            return $item['index'] <= $index ? true : false;
        });

        $this->searchItems($minAll, $index, false)->each(function ($item) use ($aux) {
            $aux->push($item);
        });

        $this->searchItems($maxAll, $index)->each(function ($item) use ($aux) {
            $aux->push($item);
        });
        return $aux->sortBy('index')->values();
    }

    //Ordena items dentro de una lista de objetos
    public function searchItems($items, $index, bool $orderAsc = true)
    {
        // return $items->values();
        $aux        = collect();
        $collection = collect();
        $tables     = $items->filter(function ($item, $key) {
            return isset($item['tables_id']);
        })->unique('index')->values();

        $countResult = 0;
        if ($tables->count() >= 2) {
            $collection->push($tables[0]);
            $collection->push($tables[1]);
            $countResult += 2;
        } else if ($tables->count() == 1) {
            $collection->push($tables[0]);
            $countResult++;
        }

        $standing = $items->filter(function ($item, $key) {
            return (@$item["standing_people"] && $item["standing_people"]['availability_standing']);
        })->unique('index')->values();

        if ($standing->count() >= 2) {
            $collection->push($standing[0]);
            $collection->push($standing[1]);
            $countResult += 2;
        } else if ($standing->count() == 1) {
            $collection->push($standing[0]);
            $countResult++;
        }

        if (!$collection->isEmpty()) {
            $collection = $orderAsc ? $collection->sortBy('index')->values() : $collection->sortByDesc('index')->values();
        }

        if ($collection->count() >= 2) {
            $aux->push($collection[0]);
            $aux->push($collection[1]);
        } else if ($collection->count() == 1) {
            $aux->push($collection->first());
            $filter = $items->filter()->reject(function ($item) use ($index) {
                return ($item['index'] == $index && collect($item["tables_id"])->isEmpty() || !(@$item["standing_people"] && $item["standing_people"]['availability_standing'])) ? true : false;
            })->unique('index')->values();
            if (!$filter->isEmpty()) {
                $aux->push($filter);
            } else {
                $aux->push($items->values()[1]);
            }
        } else {
            $aux->push($items->values()[0]);
            $aux->push($items->values()[1]);
        }
        return $aux;
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
    public function searchAvailabilityDay(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $next_day, string $timezone, int $eventId = null)
    {
        $this->validNextDate($date, $next_day, $timezone);
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
        $this->time_restriction    = $configuration->time_restriction;

        $this->checkReservationTimeTolerance($date, $this->time_tolerance, $microsite_id, $timezone, $next_day);

        if ($this->maxPeople < $num_guests) {
            abort(500, "La configuracion del sitio no soporta la esa cantidad de usuario");
        }

        $today    = Carbon::parse($date, $timezone);
        $tomorrow = $today->copy()->addDay();

        //Valida si existe disponibilidad
        $availabilityTablesInit = $this->searchTablesReservation($date, $microsite_id, $zone_id);
        if (!$availabilityTablesInit['availability']->isEmpty()) {
            //Calculo hora de cierre de local
            $dateCloseInit = Carbon::createFromFormat('Y-m-d H:i:s', $availabilityTablesInit['dayClose'] . " " . $availabilityTablesInit['hourClose'], $timezone)->addDay($availabilityTablesInit['day']);
            //Busco si existe un evento de pago
            $event = $this->checkEventPayment($date, $microsite_id, $hour, $dateCloseInit, $next_day, $timezone);
        } else {
            abort(500, "No hay turnos disponibles " . $today->formatLocalized('%A %d %B %Y'));
        }
        //Verifico si existe evento de pago
        if ($event->get("event")) {
            return collect(["event_id" => collect($event->get('event'))->only(['id', 'name', 'description', 'image', 'bs_type_event_id', 'hourMin', 'hourMax', 'day'])]);
        } else {
            $searchPromotions = false;
            $otherDay         = $this->otherDay($date, $hour, $next_day, $timezone);
            if (!$event->get('hourMax') && !$event->get('hourMin') && !isset($eventId)) {
                //Cuando no existe evento de pago ese dia
                // return $eventId;
                $aTablesE = $this->searchTablesEventFree($today, $tomorrow, $microsite_id, $zone_id);
                $aTablesN = $availabilityTablesInit;
                if (!$aTablesN['availability']->isEmpty() && !$aTablesE['availability']->isEmpty()) {
                    $idEvent            = $aTablesE['eventFree']->first()['id'];
                    $availabilityTables = $this->algoritmoTables($date, $timezone, $aTablesE, $aTablesN);
                    $dateCloseNormal    = Carbon::createFromFormat('Y-m-d H:i:s', $aTablesN['dayClose'] . " " . $aTablesN['hourClose'], $timezone)->addDay($aTablesN['day']);
                    $dateCloseEvent     = Carbon::createFromFormat('Y-m-d H:i:s', $aTablesE['dayClose'] . " " . $aTablesE['hourClose'], $timezone)->addDay($aTablesE['day']);
                    if (($dateCloseNormal->toDateTimeString() <=> $dateCloseEvent->toDateTimeString()) > 0) {
                        $dateClose  = $dateCloseNormal;
                        $indexClose = $this->defineIndexHour($aTablesN['day'], $dateClose->toTimeString());
                    } else {
                        $dateClose  = $dateCloseEvent;
                        $indexClose = $this->defineIndexHour($aTablesE['day'], $dateClose->toTimeString());
                    }
                } elseif (!$aTablesE['availability']->isEmpty()) {
                    $availabilityTables = $aTablesE;
                    $idEvent            = $aTablesE['eventFree']->first()['id'];
                    $dateClose          = Carbon::createFromFormat('Y-m-d H:i:s', $aTablesE['dayClose'] . " " . $aTablesE['hourClose'], $timezone)->addDay($aTablesE['day']);
                    $indexClose         = $this->defineIndexHour($aTablesE['day'], $dateClose->toTimeString());
                } elseif (!$aTablesN['availability']->isEmpty()) {
                    $searchPromotions   = true;
                    $availabilityTables = $aTablesN;
                    $idEvent            = null;
                    $dateClose          = Carbon::createFromFormat('Y-m-d H:i:s', $aTablesN['dayClose'] . " " . $aTablesN['hourClose'], $timezone)->addDay($aTablesN['day']);
                    $indexClose         = $this->defineIndexHour($aTablesN['day'], $dateClose->toTimeString());
                } else {
                    abort(500, "No hay turnos disponibles " . $today->formatLocalized('%A %d %B %Y'));
                }
                $hours = $this->formatActualHour($date, $hour, $next_day, $timezone, $otherDay, $availabilityTables['hourIni'], null);
            } else if (isset($eventId)) {
                //Cuando se busca disponibilidad en un evento
                $eventSearchLimit = $this->searchTypeEvent($date, $microsite_id, $hour, $dateCloseInit, $next_day, $timezone, $eventId);
                if (@$eventSearchLimit && $eventSearchLimit->get('event') !== null) {
                    $availabilityTables = $availabilityTablesInit;
                    $idEvent            = $eventSearchLimit['event']->first()['id'];

                    $hourNow     = $this->dateMaxFormat(Carbon::now($timezone));
                    $dateQueryE  = Carbon::parse($date . " " . $hour, $timezone)->addDay($next_day);
                    $compareTime = $hourNow->toDateTimeString() <=> $eventSearchLimit["hourMin"]->toDateTimeString();
                    $dateIniE    = $compareTime == 1 ? $hourNow : $dateQueryE;
                    $next_dayE   = $next_day > $eventSearchLimit["day"] ? $next_day : $eventSearchLimit["day"];

                    if ($dateQueryE->toDateTimeString() <=> $eventSearchLimit["hourMin"]->toDateTimeString() >= 0) {
                        $secelectHour = ($dateQueryE->toDateTimeString() <=> $hourNow->toDateTimeString()) == 1 ? $dateQueryE->toTimeString() : $hourNow->toTimeString();
                    } else {
                        $secelectHour = $compareTime == 1 ? $hourNow->toTimeString() : $dateQueryE->toTimeString();
                    }

                    //Se asignan los indices de busqueda para el evento
                    $otherDay     = $this->otherDay($eventSearchLimit["hourMin"]->toDateString(), $eventSearchLimit["hourMin"]->toTimeString(), $eventSearchLimit["day"], $timezone);
                    $dateClose    = $eventSearchLimit["hourMax"];
                    $indexClose   = $this->defineIndexHour($eventSearchLimit["day"], $dateClose->toTimeString());
                    $auxTimeLimit = ($dateQueryE->toDateTimeString() <=> $dateClose->toDateTimeString()) > 0 ? $eventSearchLimit["hourMax"] : null;
                    $dateAuxQ     = $dateQueryE->copy()->subDay($next_day)->toDateString();
                    $hours        = $this->formatActualHour($dateAuxQ, $secelectHour, $next_dayE, $timezone, $otherDay, $eventSearchLimit["hourMin"]->toTimeString(), $eventSearchLimit["hourMin"], $auxTimeLimit);
                } else {
                    abort(500, "No existe el evento que se desea buscar disponibilidad para " . $today->formatLocalized('%A %d %B %Y'));
                }
            } else {
                //Cuando existe un evento de pago en ese dia
                $searchPromotions              = true;
                $availabilityTables            = $availabilityTablesInit;
                $hourNow                       = $this->dateMaxFormat(Carbon::now($timezone));
                $hourInit                      = Carbon::parse($date . " " . $availabilityTables['hourIni'], $timezone)->addDay($next_day);
                $availabilityTables['hourIni'] = ($hourNow->toDateTimeString() <=> $hourInit->toDateTimeString()) >= 0 ? $hourNow->toTimeString() : $availabilityTables['hourIni'];
                $idEvent                       = null;
                $dateClose                     = $dateCloseInit;
                $indexClose                    = $this->defineIndexHour($availabilityTables['day'], $dateClose->toTimeString());
                $hours                         = $this->formatActualHour($date, $hour, $next_day, $timezone, $otherDay, $availabilityTables['hourIni']);
            }
            // return [$otherDay,$otherDayE,$next_day];
            // return $hours;
            $hourQuery        = $hours->get("hourQuery");
            $hourAvailability = $hours->get("hourAvailability");
            $hourUp           = $hours->get("hourUp");
            $hourDown         = $hours->get("hourDown");
            $indexQuery       = $this->defineIndexHour($next_day, $hourQuery->toTimeString());

            //Define indice de hora para la disponibilidad analizando con la hora de consulta
            $compareA = $hourQuery->toDateString() <=> $hourAvailability->toDateString();
            if ($compareA > 0) {
                $indexAvailability = $this->defineIndexHour(0, $hourAvailability->toTimeString());
            } else if ($compareA == 0) {
                if (($hourQuery->toDateTimeString() <=> $hourAvailability->toDateTimeString()) >= 0) {
                    $indexAvailability = $this->defineIndexHour($next_day, $hourAvailability->toTimeString());
                } else {
                    $indexAvailability = $this->defineIndexHour($next_day, $hourAvailability->toTimeString());
                }
            } else {
                $indexAvailability = $this->defineIndexHour(1, $hourAvailability->toTimeString());
            }

            //Define indice de hora para la busqueda superior analizando con la hora de consulta
            $compareHIU = $hourUp->toDateString() <=> $hourQuery->toDateString();
            if ($compareHIU > 0) {
                $indexHourInitUp = $this->defineIndexHour(1, $hourUp->toTimeString());
            } else if ($compareHIU == 0) {
                if (($hourUp->toDateTimeString() <=> $hourQuery->toDateTimeString()) >= 0) {
                    $indexHourInitUp = $this->defineIndexHour($next_day, $hourUp->toTimeString());
                } else {
                    if ($indexQuery + 1 <= $indexAvailability) {
                        $indexHourInitUp = $indexAvailability;
                    } else {
                        $indexHourInitUp = $indexQuery + 1;
                    }
                }
            } else {
                if ($indexQuery + 1 <= $indexAvailability) {
                    $indexHourInitUp = $indexAvailability;
                } else {
                    $indexHourInitUp = $indexQuery + 1;
                }
            }

            //Define indice de hora para la inferior analizando con la hora de consulta
            $compareHID = $hourDown->toDateString() <=> $hourQuery->toDateString();
            if ($compareHID > 0) {
                $indexHourInitDown = $this->defineIndexHour(0, $hourDown->toTimeString());
            } else if ($compareHID == 0) {
                if (($hourDown->toDateTimeString() <=> $hourQuery->toDateTimeString()) > 0) {
                    $indexHourInitDown = $this->defineIndexHour($next_day, $hourDown->toTimeString());
                } else {
                    if ($indexQuery - 1 >= $indexClose) {
                        $indexHourInitDown = $indexClose;
                    } else {
                        $indexHourInitDown = $indexQuery - 1;
                    }
                }
            } else {
                if ($indexQuery - 1 >= $indexClose) {
                    $indexHourInitDown = $indexClose;
                } else {
                    $indexHourInitDown = $indexQuery - 1;
                }
            }

            // return ["Query" => $indexQuery, "Availability" => $indexAvailability, "Up" => $indexHourInitUp, "Down" => $indexHourInitDown, "Close" => $indexClose];
            // Calculode disponibilidad si existe un evento de pago
            if ($event->get("hourMax") !== null && $event->get("hourMin") !== null) {

                $nextMin = 0;
                if (($event->get('hourMin')->toDateString() <=> $event->get('hourMax')->toDateString()) < 0) {
                    $nextMax = 1;
                } else {
                    if (($hourQuery->toDateString() <=> $event->get('hourMax')->toDateString()) > 0) {
                        $nextMax = 0;
                    } else {
                        $nextMax = $nextMin = $next_day;
                    }
                }
                $indexHourMax = $this->defineIndexHour($nextMax, $event->get('hourMax')->toTimeString());
                $indexHourMin = $this->defineIndexHour($nextMin, $event->get('hourMin')->toTimeString());
                if ($next_day == 0) {
                    if ($searchPromotions) {
                        $availabilitySinPromociones = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexAvailability, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $idEvent);
                        $aux                        = collect();
                        foreach ($availabilitySinPromociones as $availability) {
                            if (count($availability['tables_id']) > 0 || (@$availability["standing_people"] && $availability["standing_people"]['availability_standing'])) {
                                //Buscar promociones se tiene $date & $hour
                                //Servicio para buscar las promociones
                                $promotionsId = $this->searchPromotions($availability['form']['date'], $availability['form']['next_day'], $availability['hour'], $microsite_id, $timezone);
                                if ($promotionsId->count() > 0) {
                                    // $availability['form']['event_id'] = $promotionsId;
                                    $availability['promotions'] = $promotionsId;

                                } else {
                                    // $availability['form']['event_id'] = null;
                                    $availability['promotions'] = null;
                                }
                            }
                            $aux->push($availability);
                        }
                        return $aux;
                    } else {
                        return $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexAvailability, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $idEvent);
                    }

                } else {
                    return $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMax, $indexHourMin, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $idEvent);
                }

            } else {
                //Calculo de disponibilidad si no existe un evento de pago
                $indexHourMax = $indexClose;
                $indexHourMin = $indexAvailability;

                //Se busca eventos gratuitos
                if (!$searchPromotions) {
                    $availabilityEventsFree = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMin, $indexHourMax, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $idEvent);
                    $aux                    = collect();
                    foreach ($availabilityEventsFree as $availabilityFree) {
                        if (count($availabilityFree['tables_id']) > 0 || (@$availabilityFree["standing_people"] && $availabilityFree["standing_people"]['availability_standing'])) {

                            //Si existe un evento gratuito
                            if (!isset($eventId) && !isset($eventSearchLimit['event'])) {
                                //Buscar fecha de inicio del evento
                                $eventFree     = ev_event::where('status', 1)->find($availabilityFree['promotions']);
                                $eventInitTime = $eventFree->datetime_event;
                                $date          = $next_day == 1 ? Carbon::createFromFormat('Y-m-d', $date, $timezone)->addDay()->toDateString() : $date;
                                $dateHour      = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $availabilityFree['hour'], $timezone)->toDateTimeString();
                                if ($dateHour < $eventInitTime) {
                                    $promotionsId                         = $this->searchPromotions($date, $next_day, $availabilityFree['hour'], $microsite_id, $timezone);
                                    $availabilityFree['form']['event_id'] = null;
                                    $availabilityFree['promotions']       = $promotionsId->count() ? $promotionsId : null;
                                } else {
                                    $availabilityFree['promotions'] = null;
                                }
                            } else {
                                //Cuando se busca dentro de un horario de un evento
                                // return "test";
                                //Buscar fecha de inicio del evento
                                $eventFree     = ev_event::where('status', 1)->find($availabilityFree['promotions']);
                                $eventInitTime = $eventFree->datetime_event;
                                $date          = $next_day == 1 ? Carbon::createFromFormat('Y-m-d', $date, $timezone)->addDay()->toDateString() : $date;
                                $dateHour      = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $availabilityFree['hour'], $timezone)->toDateTimeString();
                                if ($dateHour < $eventInitTime) {
                                    if (isset($eventSearchLimit['event'])) {
                                        $promotionsId = $dateHour >= $eventInitTime ? collect($eventId) : collect();
                                    } else {
                                        $promotionsId = $this->searchPromotions($date, $next_day, $availabilityFree['hour'], $microsite_id, $timezone);
                                    }
                                    $availabilityFree['form']['event_id'] = $promotionsId->count() ? $promotionsId : null;
                                } else {
                                    $availabilityFree['promotions'] = null;
                                }
                            }
                        }
                        $aux->push($availabilityFree);
                    }
                    return $aux;
                } else {
                    //Verifica si se buscan promociones en la disponibilidad
                    $availabilitySinPromociones = $this->searchAvailavilityFormat($indexQuery, $indexAvailability, $indexHourInitDown, $indexHourInitUp, $indexHourMin, $indexHourMax, $microsite_id, $date, $hourQuery, $num_guests, $zone_id, $timezone, $availabilityTables['availability'], $idEvent);
                    $aux                        = collect();
                    foreach ($availabilitySinPromociones as $availability) {
                        if (count($availability['tables_id']) > 0 || (@$availability["standing_people"] && $availability["standing_people"]['availability_standing'])) {

                            //Buscar promociones se tiene $date & $hour
                            //Servicio para buscar las promociones
                            // return $availability;
                            $promotionsId = $this->searchPromotions($availability['form']['date'], $availability['form']['next_day'], $availability['hour'], $microsite_id, $timezone);
                            if ($promotionsId->count() > 0) {
                                // $availability['form']['event_id'] = $promotionsId;
                                $availability['promotions'] = $promotionsId;
                            } else {
                                // $availability['form']['event_id'] = null;
                                $availability['promotions'] = null;
                            }
                        }
                        $aux->push($availability);
                    }
                    return $aux;
                }
            }

        }
    }

    //Le da formato de disponibilidad en una fecha y hora determinada ** 2 Down / 1 Mid / 2 Top **
    public function searchAvailavilityFormat(int $indexQuery, int $indexAvailability, int $indexHourInitDown, int $indexHourInitUp, int $indexHourMin, int $indexHourMax, int $microsite_id, string $date, Carbon $hourQuery, int $num_guests, int $zone_id, string $timezone, $availabilityTables, int $eventId = null)
    {
        $arrayMid   = collect();
        $resultsMid = [];
        // $indexQueryAux = $indexQuery;
        if ($indexQuery < $indexAvailability) {
            // $indexQuery      = $indexAvailability;
            $indexHourInitUp = $indexAvailability;
        } else if ($indexQuery <= $indexHourMax) {
            $resultsMid = $this->getAvailabilityBasic($microsite_id, $date, $hourQuery->toTimeString(), $num_guests, $zone_id, $indexQuery, $timezone, $availabilityTables, $eventId);
            if ($indexQuery - $indexAvailability <= 2 && $indexQuery - $indexAvailability > 0) {
                $indexHourMin--;
            }
        }
        // return ;
        // dd("test");
        if (count($resultsMid) > 0) {
            $arrayMid->push($resultsMid);
        } else {
            $arrayMid->push(["index" => $indexQuery, "hour" => $hourQuery->toTimeString(), "tables_id" => null, "form" => null, "promotions" => null]);
        }

        $arrayUp = $this->searchUpAvailability($indexHourInitUp, $microsite_id, $date, $num_guests, $zone_id, $indexHourMax, $timezone, $availabilityTables, $eventId);

        $arrayDown = $this->searchDownAvailability($indexHourInitDown, $microsite_id, $date, $num_guests, $zone_id, $indexHourMin, $timezone, $availabilityTables, $eventId);

        $cantUp = $arrayUp->count();
        if ($cantUp < 2) {
            $arrayUp = $this->addUpAvailavility($arrayUp, $indexQuery + $cantUp + 1, $indexHourMax, $eventId);
        }
        $cantDown = $arrayDown->count();
        if ($cantDown < 2) {
            $arrayDown = $this->addDownAvailavility($arrayDown, $indexQuery - $cantDown, $indexHourMin, $eventId);
        }
        // dd("test");
        return collect(array_merge($arrayDown->toArray(), $arrayMid->toArray(), $arrayUp->toArray()));
    }
/**
 * permite calcula la fecha actual en el formato de multiplo de 15 minutos y devolverte la fecha actual, fecha permitida de busqueda asi como la hora busqueda superio
 * @param  string $hour     hora de busqueda
 * @param  string $timezone timezone del micrositio
 * @param  int    $next_day valor de 0 y 1
 * @return array           devuelve un array con la hora de busqueda, hora actual y hora de busqueda superior inicial
 */
    public function formatActualHour(string $date, string $hourQuery, int $next_day, string $timezone, bool $otherDay, string $hourInitOtherDay, Carbon $hourAuxLimitIni = null, Carbon $hourAuxLimitFin = null)
    {

        $hourQuery    = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hourQuery, $timezone)->addDay($next_day);
        $hourQueryAux = $hourQuery->copy();

        $timeDate = Carbon::parse($date, $timezone);
        $now      = Carbon::now($timezone);

        if ($otherDay) {
            $hourAvailability = isset($hourAuxLimitIni) ? $hourAuxLimitIni : Carbon::parse($date . " " . $hourInitOtherDay, $timezone);
        } else {
            $dateCompare = $timeDate->toDateString() <=> $now->toDateString();
            if ($dateCompare == 0) {
                $hourAvailability = isset($hourAuxLimitIni) ? $hourAuxLimitIni : $this->dateMaxFormat(Carbon::now($timezone));
            } else if ($dateCompare > 0) {
                $hourAvailability = isset($hourAuxLimitIni) ? $hourAuxLimitIni : $this->dateMaxFormat($hourQuery);
            }
        }
        // return $now->copy()->addMinutes($this->time_restriction);
        $newDate = $now->copy()->addMinutes($this->time_restriction);
        if ($newDate->toDateTimeString() >= $hourAvailability->toDateTimeString()) {
            $hourAvailability = $this->dateMaxFormat($newDate);
            // $this->dateMaxFormat($hourAvailability->addMinutes($this->time_restriction));
        }

        if ($hourQueryAux <= $hourAvailability) {
            $hourQueryAux = $hourAvailability->copy();
            $hourInitDown = $hourQueryAux->copy();
        } else {
            $hourQueryAux = $hourQuery->copy();
            $hourInitDown = $hourQueryAux->copy()->subMinutes(15);
        }
        $hourInitUp = $hourQueryAux->copy()->addMinutes(15);
        if ($hourAuxLimitFin) {
            $hourInitDown = $hourAuxLimitFin->copy()->subMinutes(15);
            $hourInitUp   = $hourAuxLimitFin->copy()->addMinutes(15);
        }
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
    public function searchUpAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id, int $indexHourMax, string $timezone, $availabilityTables, int $eventId = null)
    {
        $arrayUp     = collect();
        $indexUpHour = $indexHourInit;
        // return "test2";
        // return $this->timeForTable->timeToIndex("00:00:00");
        // return
        // return $this->timeForTable->indexToTime(120);
        while ($indexUpHour <= $indexHourMax) {
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
    public function searchDownAvailability(int $indexHourInit, int $microsite_id, string $date, int $num_guests, int $zone_id, int $indexHourMin, string $timezone, $availabilityTables, int $eventId = null)
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
    public function addUpAvailavility($arrayUp, int $indexHourInit, int $indexHourMax, int $eventId = null)
    {
        $countUp    = $arrayUp->count();
        $indexUpAux = $indexHourInit;
        for ($i = $countUp; $i < 2; $i++) {
            if ($indexUpAux < $indexHourMax) {
                $arrayUp->push(["index" => $indexUpAux, "hour" => $this->timeForTable->indexToTime($indexUpAux), "tables_id" => null, "form" => null, "promotions" => null]);
                $indexUpAux++;
            } else {
                $arrayUp->push(["index" => $indexUpAux, "hour" => null, "tables_id" => null, "form" => null, "promotions" => null]);
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
    public function addDownAvailavility($arrayDown, int $indexHourInit, int $indexHourActualAux, int $eventId = null)
    {
        $countDown    = $arrayDown->count();
        $indexDownAux = $indexHourInit - 1;
        for ($i = $countDown; $i < 2; $i++) {
            if ($indexDownAux >= $indexHourActualAux) {
                $arrayDown->prepend(["index" => $indexDownAux, "hour" => $this->timeForTable->indexToTime($indexDownAux), "tables_id" => null, "form" => null, "promotions" => null]);
                $indexDownAux--;
            } else {
                $arrayDown->prepend(["index" => $indexDownAux, "hour" => null, "tables_id" => null, "form" => null, "promotions" => null]);
            }
        }
        return $arrayDown;
    }

/**
 * Busca disponibilidad de una mesa en un hora determinada
 * @param  int    $microsite_id id del microsito a buscarreturn "test"
 * @param  string $date         fecha de la reservacion
 * @param  string $hour         hora de busqueda de la reservacion
 * @param  int    $num_guests   numero de cliente
 * @param  int    $zone_id      zona donde se desea realizar la reservacion
 * @param  int    $indexHour    index de la hora que se desea realizar la busqueda
 * @return array               id de las mesas disponibles para esa fecha y hora determinaada
 */
    public function getAvailabilityBasic(int $microsite_id, string $date, string $hour, int $num_guests, int $zone_id, int $indexHour, string $timezone, $availabilityTables, int $eventId = null)
    {
        $next_day = $indexHour >= 96 ? 1 : 0;
        // return $availabilityTables;
        $timeFoTable                = new TimeForTable;
        $availabilityTablesFilter   = [];
        $unavailabilityTablesFilter = [];
        $availabilityTablesId       = [];
        $availabilityTablesIdFinal  = [];

        list($year, $month, $day) = explode("-", $date);
        list($h, $m, $s)          = explode(":", $hour);
        list($hd, $md, $sd)       = explode(":", $this->durationTimeAux);
        $startHour                = Carbon::create($year, $month, $day, $h, $m, $s, $timezone)->addDay($next_day);
        $endHour                  = Carbon::create($year, $month, $day, $h, $m, $s, $timezone)->addDay($next_day)->addHours($hd)->addMinutes($md)->addSeconds($sd);
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

        $listReservationsTemp = $this->getReservationTemp($availabilityTablesId->toArray(), $date, $hour, $timezone, $microsite_id, $next_day);

        $unavailabilityTablesFilter = collect(array_merge($listBlocks, $listReservations, $listReservationsTemp))->unique();

        $availabilityTablesId = $availabilityTablesId->diff($unavailabilityTablesFilter);

        //Filtrar de las mesas disponibles la cantidad de usuarios
        $availabilityTablesIdFinal = $this->availabilityTablesIdFinal($availabilityTablesId->toArray(), $num_guests);
        $nextDay                   = $indexHour >= 96 ? 1 : 0;
        $formInfo                  = ["date" => $date, "hour" => $hour, "next_day" => $nextDay, "event_id" => $eventId, "zone_id" => $zone_id, "num_guest" => $num_guests];
        if ($availabilityTablesIdFinal->count() > 0) {
            return ["index" => $indexHour, "hour" => $hour, "tables_id" => [$availabilityTablesIdFinal->first()], "form" => $formInfo, "promotions" => $eventId];
        } else {
            $availabilityTablesIdFinal = $this->algoritmoAvailability($availabilityTablesId->toArray(), $num_guests);
            // return ["hour" => $hour, "tables_id" => $availabilityTablesIdFinal];
            if (isset($availabilityTablesIdFinal)) {
                return ["index" => $indexHour, "hour" => $hour, "tables_id" => $availabilityTablesIdFinal, "form" => $formInfo, "promotions" => $eventId];
            } else {
                $availabilityTablesIdFinal = $this->checkReservationStandingPeople($date, $hour, $this->time_tolerance, $timezone, $microsite_id, $num_guests);

                return ["index" => $indexHour, "hour" => $hour, "tables_id" => null, "standing_people" => $availabilityTablesIdFinal, "form" => $formInfo, "promotions" => $eventId];
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
                ->whereRaw("ADDDATE(concat(start_date,' ',start_time), INTERVAL next_day DAY) <= ?", array($hourI))
                ->whereRaw("ADDDATE(concat(start_date,' ',end_time),INTERVAL next_day DAY )>= ?", array($hourI))
                ->orwhereRaw("ADDDATE(concat(start_date,' ',start_time),INTERVAL next_day DAY )< ?", array($hourF))
                ->whereRaw("ADDDATE(concat(start_date,' ',end_time),INTERVAL next_day DAY )>= ?", array($hourF));
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
        // return $hourI;
        $listReservation = [];
        $reservations    = res_table_reservation::whereIn('res_table_id', $tables_id)->with(['reservation' => function ($query) use ($date, $hourI, $hourF) {
            return $query->where('date_reservation', '=', $date)
                ->where('res_reservation_status_id', '<>', $this->id_status_released)
                ->where('res_reservation_status_id', '<>', $this->id_status_cancel)
                ->where('res_reservation_status_id', '<>', $this->id_status_absent)
                ->where("datetime_input", '<=', $hourI)
                ->where("datetime_output", '>=', $hourI)
                ->orwhere("datetime_input", '<', $hourF)
                ->where("datetime_output", '>=', $hourF);
        }])->get();
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
    private function checkReservationTimeTolerance(string $date, string $time_tolerance, int $microsite_id, string $timezone, int $next_day)
    {
        if ($time_tolerance !== 0) {
            // $date                  = Carbon::parse($date, $timezone)->addDay($next_day)->toDateString();
            $time_tolerance_string = Carbon::createFromTime(0, 0, 0, $timezone)->addMinutes($time_tolerance)->toTimeString();
            $dateActual            = Carbon::now($timezone);
            $hourActual            = $dateActual->toDateTimeString();
            $reservations          = Reservation::where("ms_microsite_id", $microsite_id)
                ->where("date_reservation", $date)
                ->where("res_source_type_id", $this->id_res_source_type)
                ->where('res_reservation_status_id', $this->id_status_reserved)
                ->whereRaw("ADDDATE(addtime(concat(date_reservation,' ',hours_reservation),?) ,  INTERVAL next_day DAY) <= ?", array($time_tolerance_string, $hourActual))->get();
            if (!$reservations->isEmpty()) {
                foreach ($reservations as $reservation) {
                    $reservation->res_reservation_status_id = $this->id_status_cancel;
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

    private function checkEventPaymentAux(string $date, int $microsite_id, string $hour, Carbon $dateCloseInit, int $next_day, string $timezone)
    {
        $dateQuery = Carbon::parse($date . " " . $hour, $timezone)->addDay($next_day);
        $today     = Carbon::parse($date, $timezone);
        $event     = ev_event::where('ms_microsite_id', $microsite_id)
            ->where('status', 1)
            ->where('bs_type_event_id', $this->id_event_payment)
            ->where('datetime_event', '>=', $today->toDateTimeString())
            ->where('datetime_event', '<=', $dateCloseInit->toDateTimeString())
            ->first();
        if (isset($event)) {
            $dateEvent   = $this->dateMinFormat(Carbon::parse($event->datetime_event, $timezone));
            $compareDate = $dateCloseInit->toDateString() <=> $dateEvent->toDateString();
            $day         = $compareDate == 1 ? 1 : 0;
            if ($dateEvent->toDateTimeString() <= $dateQuery->toDateTimeString() && $dateQuery->toDateTimeString() <= $dateCloseInit->toDateTimeString()) {
                // $event->hourMin = $this->getIndexHour($today, $dateEvent, $next_day);
                // $event->hourMax = $this->getIndexHour($today, $dateCloseInit, $next_day);
                $event->hourMin = $dateEvent;
                $event->hourMax = $dateCloseInit;
                $event->day     = $day;
                return collect($event)->only(['id', 'name', 'description', 'image', 'bs_type_event_id', 'hourMin', 'hourMax', 'day']);
            }
        }
        return collect();
    }

    private function checkEventPayment(string $date, int $microsite_id, string $hour, Carbon $dateCloseInit, int $next_day, string $timezone)
    {
        // return $dateCloseInit;
        $dateQuery = Carbon::parse($date . " " . $hour, $timezone)->addDay($next_day);
        $today     = Carbon::parse($date . " " . "00:00:00", $timezone);
        $event     = ev_event::where('ms_microsite_id', $microsite_id)
            ->where('status', 1)
            ->where('bs_type_event_id', $this->id_event_payment) //1:eventogratuito 2:eventopaga 3:promocion gratis 4 promocion de paga
            ->where('datetime_event', '<=', $dateCloseInit->toDateTimeString())
            ->Where('datetime_event', '>=', $today->toDateTimeString())
            ->first();
        if (isset($event)) {
            $dateEvent   = $this->dateMinFormat(Carbon::parse($event->datetime_event, $timezone));
            $compareDate = $dateCloseInit->toDateString() <=> $dateEvent->toDateString();
            $day         = $compareDate == 1 ? 1 : 0;
            if ($dateEvent->toDateTimeString() <= $dateQuery->toDateTimeString() && $dateQuery->toDateTimeString() <= $dateCloseInit->toDateTimeString()) {
                $event->hourMin = $dateEvent;
                $event->hourMax = $dateCloseInit;
                $event->day     = $day;
                return collect(["event" => $event, "hourMin" => $dateEvent, "hourMax" => $dateCloseInit, "type_event" => $this->id_event_payment]);
            } else if ($dateQuery->toDateTimeString() > $dateCloseInit->toDateTimeString()) {
                abort(500, "Horario no disponible");
            } else {
                return collect(["event" => null, "hourMin" => $dateEvent, "hourMax" => $dateCloseInit, "type_event" => $this->id_event_payment]);

            }
        } else {
            return collect(["event" => null, "hourMin" => null, "hourMax" => null, "type_event" => null]);
        }
    }

    private function checkEventFree(string $date, int $microsite_id, string $hour, Carbon $dateCloseInit, int $next_day, string $timezone, int $idEvent = null)
    {
        $today = Carbon::parse($date, $timezone);
        // return $dateCloseInit;
        if (isset($idEvent)) {
            $events = ev_event::with("turn")
                ->where('status', 1)
                ->where('id', $idEvent)
                ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
                ->get();
        } else {
            $events = ev_event::with("turn", "turn.zones")
                ->where([
                    ['status', 1],
                    ['datetime_event', '>=', $today->toDateTimeString()],
                    ['datetime_event', '<', $dateCloseInit->toDateTimeString()],
                    ['bs_type_event_id', $this->id_event_free],
                    ['ms_microsite_id', $microsite_id],
                ])->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
                ->get();
        }
        if (!$events->isEmpty()) {
            $dateEvent   = $this->dateMinFormat(Carbon::parse($date . " " . $events->first()->turn["hours_ini"], $timezone));
            $compareDate = $dateCloseInit->toDateString() <=> $dateEvent->toDateString();
            $day         = $compareDate == 1 ? 1 : 0;
            $dayCloseE   = Carbon::parse($date . " " . $events->first()->turn["hours_end"])->addDay($day);
            if (isset($idEvent)) {
                return collect(["event" => $events, "hourMin" => $dateEvent, "hourMax" => $dayCloseE, "type_event" => $this->id_event_free, "day" => $day]);
            } else {
                $events->first()->hourMin = $dateEvent;
                $events->first()->hourMax = $dayCloseE;
                $events->first()->day     = $day;
                return collect($events->first())->only(['id', 'name', 'description', 'image', 'bs_type_event_id', 'hourMin', 'hourMax', 'day']);
            }
        } else {
            return isset($idEvent) ? collect(["event" => null, "hourMin" => null, "hourMax" => null, "type_event" => null, "day" => $day]) : $events;
        }
    }

    private function checkEventPromo(string $date, int $microsite_id, string $hour, Carbon $dateCloseInit, int $next_day, string $timezone, int $idEvent = null)
    {
        $dayC = Carbon::parse($date, $timezone);
        $day  = $dayC->dayOfWeek;
        if (isset($idEvent)) {
            $promotions = ev_event::where('id', $idEvent)
                ->where('status', 1)
                ->with(['turns' => function ($query) use ($hour, $microsite_id) {
                    $query->where('bs_type_event_id', $this->id_promotion)
                        ->where('ms_microsite_id', $microsite_id)->get();
                }, 'turns.days' => function ($query) use ($day) {
                    $query->where('day', $day)->get();
                }])->get();
        } else {
            $promotions = ev_event::where('date_expire', '>=', $date)
                ->where([
                    ['status', 1],
                    ['bs_type_event_id', $this->id_promotion],
                    ['ms_microsite_id', $microsite_id],
                ])
                ->with(['turns' => function ($query) use ($hour, $microsite_id) {
                    $query->where('bs_type_event_id', $this->id_promotion)
                        ->where('ms_microsite_id', $microsite_id)
                        ->where('status_web', 1)
                        ->get();
                }, 'turns.days' => function ($query) use ($day) {
                    $query->where('day', $day)->get();
                }])->get();
        }

        $promoaux = collect();
        if ($promotions->count() > 0) {
            foreach ($promotions as $promotion) {
                if ($promotion->turns->count() > 0) {
                    foreach ($promotion->turns as $turn) {
                        if ($turn->days->count() > 0) {
                            foreach ($turn->days as $day) {
                                if ($turn->hours_ini_web !== null && $turn->hours_end_web !== null) {
                                    $hourMin            = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turn->hours_ini_web, $timezone);
                                    $hourMax            = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turn->hours_end_web, $timezone);
                                    $compareDate        = $hourMax->toDateString() <=> $hourMin->toDateString();
                                    $promotion->hourMin = $hourMin;
                                    $promotion->hourMax = $hourMax;
                                    $promotion->day     = $compareDate == 1 ? 1 : 0;
                                } else {
                                    $hourMin            = Carbon::parse($date, $timezone);
                                    $hourMax            = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . "05:45:00", $timezone)->addDay();
                                    $promotion->hourMin = $hourMin;
                                    $promotion->hourMax = $hourMax;
                                    $promotion->day     = 1;
                                }
                                $promoaux->push(collect($promotion)->only(['id', 'name', 'description', 'image', 'bs_type_event_id', 'hourMin', 'hourMax', 'day']));

                            }
                        }

                    }
                }

            }
        }
        if ($promoaux->count() > 0) {
            $compareDate = $hourMax->toDateString() <=> $hourMin->toDateString();
            $dayAux      = $compareDate == 1 ? 1 : 0;
            if (isset($idEvent)) {
                return collect(["event" => $promoaux, "hourMin" => $hourMin, "hourMax" => $hourMax, "type_event" => $this->id_promotion, "day" => $dayAux]);
            } else {
                return $promoaux;
            }
        } else {
            return isset($idEvent) ? collect(["event" => null, "hourMin" => null, "hourMax" => null, "type_event" => null, "day" => null]) : $promoaux;
        }
    }

    private function getIndexHour(Carbon $today, Carbon $date, $next_day)
    {
        $compare = $today->toDateString() <=> $date->toDateString();
        if ($compara == 0) {
            $index = $this->defineIndexHour($next_day, $date->toTimeString());
        } else {
            $index = $this->defineIndexHour();
        }
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

    public function checkReservationStandingPeople(string $date, string $hour, string $time_tolerance, string $timezone, int $microsite_id, int $num_guests)
    {
        // [$date, $hour, $timezone];
        $dateC      = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $timezone);
        $dateActual = Carbon::now($timezone);
        if (($dateC->toDateString() <=> $dateActual->toDateString()) > 0) {
            $dateActual = $dateC->copy();
        }
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
            return collect(["availability_standing" => false, "num_guest_availability" => $cantGuest, "num_guest_s_max" => $this->max_people_standing]);
        }
    }

    public function searchTablesEventFree(Carbon $today, Carbon $tomorrow, int $microsite_id, int $zone_id)
    {
        $eventsFree = ev_event::with("turn")
            ->where('status', 1)
            ->where('datetime_event', '>=', $today->toDateTimeString())
            ->where('datetime_event', '<', $tomorrow->toDateTimeString())
            ->where('bs_type_event_id', $this->id_event_free)
            ->where('ms_microsite_id', $microsite_id)
            ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
            ->get();
        if (!$eventsFree->isEmpty()) {
            $indexLimit = collect();
            foreach ($eventsFree as $eventFree) {
                $dayCloseEvent  = $today->toDateString();
                $hourIniEvent   = $eventFree['turn']['hours_ini'];
                $hourCloseEvent = $eventFree['turn']['hours_end'];
                $day            = ($eventFree['turn']['hours_ini'] <=> $eventFree['turn']['hours_end']) == 1 ? 1 : 0;
                $turn           = $eventFree['turn'];
                $indexLimit->push(["init" => $eventFree['turn']['hours_ini'], "final" => $eventFree['turn']['hours_end'], "day" => $day, "id" => $eventFree['id'], "id_event" => $eventFree['turn']['id']]);
                $availabilityTablesEvents = ($this->turnService->getListTable($eventFree['turn']['id'], $zone_id));
            };
            return ['availability' => $availabilityTablesEvents, 'dayClose' => $dayCloseEvent, 'hourIni' => $hourIniEvent, 'hourClose' => $hourCloseEvent, 'day' => $day, 'eventFree' => $eventsFree, 'turn' => $turn, "indexLimit" => $indexLimit];
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
        if (count($turnsFilter) > 0) {
            $hourIni    = "23:45:00";
            $indexLimit = collect();
            foreach ($turnsFilter as $turn) {
                $indexHourInit = $hourIni <=> $turn['turn']['hours_ini'];
                $dayClose      = $turn['end_date'];
                $hourIni       = $indexHourInit == 1 ? $turn['turn']['hours_ini'] : $hourIni;

                $hourClose = $turn['turn']['hours_end'];
                $day       = $turn['turn']['hours_ini'] > $turn['turn']['hours_end'] ? 1 : 0;
                $turnHour->push($turn['turn']);
                $indexLimit->push(["init" => $turn['turn']['hours_ini'], "final" => $turn['turn']['hours_end'], "day" => $day, 'id' => $turn['turn']['id']]);
                $availabilityTables->push($this->turnService->getListTable($turn['turn']['id'], $zone_id));
            };
            // return $availabilityTables;
            return ['availability' => $availabilityTables, 'dayClose' => $dayClose, 'hourIni' => $hourIni, 'hourClose' => $hourClose, 'day' => $day, 'turn' => $turnHour, "indexLimit" => $indexLimit];
        } else {
            return ['availability' => collect()];
        }
    }

    public function algoritmoTables(string $date, string $timezone, $availabilityTablesEvents, $availabilityTablesNormal)
    {
        // return [$availabilityTablesEvents, $availabilityTablesNormal];
        $dataRange = $this->formatMixEvent($date, $timezone, $availabilityTablesEvents, $availabilityTablesNormal);
        // return $this->timeForTable->indexToTime(96);
        $turnRange  = $this->defineIndexTurnEvent($date, $timezone, $availabilityTablesEvents, $availabilityTablesNormal);
        $indexLimit = $this->defineIndexLimit($turnRange);
        $eventId    = $availabilityTablesEvents['eventFree']->first()->id;
        $turnEvent  = $dataRange['event'];
        $turnNormal = $dataRange['normal']->push($turnEvent);
        $initE      = $dataRange['initE'];
        $finE       = $dataRange['finE'];
        $dayClose   = $availabilityTablesEvents['dayClose'] > $availabilityTablesNormal['dayClose'] ? $availabilityTablesEvents['dayClose'] : $availabilityTablesNormal['dayClose'];
        $hourIni    = $availabilityTablesEvents['hourIni'] < $availabilityTablesNormal['hourIni'] ? $availabilityTablesEvents['hourIni'] : $availabilityTablesNormal['hourIni'];
        $hourClose  = $availabilityTablesEvents['hourClose'] > $availabilityTablesNormal['hourClose'] ? $availabilityTablesEvents['hourClose'] : $availabilityTablesNormal['hourClose'];
        $day        = $availabilityTablesEvents['day'] > $availabilityTablesNormal['day'] ? $availabilityTablesEvents['day'] : $availabilityTablesNormal['day'];
        // $eventFree                = $availabilityTablesEvents['eventFree'];
        // $turn                     = $turnNormal;
        $availabilityTurn         = collect();
        $availabilityAvailability = collect();
        foreach ($turnRange as $range) {
            $normal = $turnNormal->where('id', $range['id'])->first();
            if (isset($normal)) {
                $availavilityAux = collect();
                foreach ($normal['tables'] as $tableNormal) {
                    // return $tableNormal;
                    $tableEvent = $turnEvent['tables']->where('id', $tableNormal['id'])->first();
                    if (isset($tableEvent)) {
                        $auxN = $tableNormal['availability'];
                        for ($i = 0; $i <= 119; $i++) {
                            if ($range['init'] <= $i && $i <= $range['final']) {
                                if ($tableEvent['availability'][$i]['rule_id'] == 2 && $i <= $finE && $i >= $initE) {
                                    $auxN[$i]['rule_id']  = 2;
                                    $auxN[$i]['event']    = true;
                                    $auxN[$i]['event_id'] = $eventId;
                                } else if ($tableEvent['availability'][$i]['rule_id'] !== 2 && $i <= $finE && $i >= $initE) {
                                    $auxN[$i]['rule_id']  = -1;
                                    $auxN[$i]['event']    = false;
                                    $auxN[$i]['event_id'] = null;
                                } else {
                                    $auxN[$i]['rule_id']  = $tableNormal['availability'][$i]['rule_id'];
                                    $auxN[$i]['event']    = false;
                                    $auxN[$i]['event_id'] = null;
                                }
                            } else {
                                $auxN[$i]['rule_id']  = -1;
                                $auxN[$i]['event']    = false;
                                $auxN[$i]['event_id'] = null;
                            }

                        }
                        $aux['id']           = $tableNormal['id'];
                        $aux['name']         = $tableNormal['name'];
                        $aux['min_cover']    = $tableNormal['min_cover'];
                        $aux['max_cover']    = $tableNormal['max_cover'];
                        $aux['availability'] = $auxN;
                        $availavilityAux->push($aux);
                    } else {
                        return $tableNormal;
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
        $finalFormat['dayClose']     = $dayClose;
        $finalFormat['hourIni']      = $hourIni;
        $finalFormat['hourClose']    = $hourClose;
        $finalFormat['day']          = $day;
        $finalFormat['turn']         = $availabilityTurn;
        $finalFormat['indexLimit']   = $indexLimit;

        $finalFormat['event_id'] = $eventId;
        if ($availabilityTablesEvents['day'] > $availabilityTablesNormal['day']) {
            $finalFormat['day'] = $availabilityTablesEvents['day'];
        } else {
            $finalFormat['day'] = $availabilityTablesNormal['day'];
        }
        return $finalFormat;
    }

    private function defineIndexTurnEvent(string $date, string $timezone, $availabilityTablesEvents, $availabilityTablesNormal)
    {
        $indexEvent  = $availabilityTablesEvents['indexLimit'];
        $indexNormal = $availabilityTablesNormal['indexLimit'];
        $itemE       = $this->changeHourIndex($indexEvent)->map(function ($item) {
            $aux['init']  = $item['init'];
            $aux['final'] = $item['final'];
            $aux['id']    = $item['id_event'];
            return $aux;
        })->first();
        $itemsN = $this->changeHourIndex($indexNormal);
        return $this->indexLimitFormat($itemE, $itemsN);
    }

    public function indexLimitFormat($indexEvent, $indexNormal)
    {
        $plane    = $indexNormal->push($indexEvent)->sortBy("init");
        $planeAux = $plane->map(function ($item) {
            unset($item['id']);
            return $item;
        })->flatten()->unique()->sort();
        $init     = $planeAux->shift();
        $planeNew = collect();
        $maxKey   = $planeAux->count() - 1;
        foreach ($planeAux as $key => $item) {
            $test['init']  = $init;
            $test['final'] = $maxKey == $key ? $item : $item - 1;
            $init          = $item;
            $planeNew->push($test);
        }
        // return $planeNew;
        $planeNew->transform(function ($item) use ($plane) {
            $id   = null;
            $find = false;
            foreach ($plane as $value) {
                if ($value['init'] <= $item['init'] && $item['init'] < $value['final'] && !$find) {
                    $id   = $value['id'];
                    $find = true;
                }
            }
            $item['id'] = $id;
            return $item;
        });
        $planeDelete = $planeNew->where('id', null);
        $planeNew    = $planeNew->diffKeys($planeDelete)->values()->all();
        // $test = $planeNew->first();
        // foreach ($planeNew as $key => $plane) {
        //     if($key>0 && $plane['init']-$test['init']>1){
        //         $test['init'];
        //     }
        // }
        return $planeNew;
    }

    public function defineIndexLimit($rangeIndex)
    {
        $indexLimit = collect();
        foreach ($rangeIndex as $index) {
            $limit['init']  = $this->timeForTable->indexToTime($index["init"]);
            $limit['final'] = $this->timeForTable->indexToTime($index["final"]);
            $limit['day']   = $index["final"] >= 96 ? 1 : 0;
            $limit['id']    = $index["id"];
            $indexLimit->push($limit);
        }
        return $indexLimit;
    }

    private function changeHourIndex($indexEvent)
    {
        return $indexEvent->map(function ($item) {
            $diff = $item['final'] <=> $item['init'];
            if ($diff >= 0 && $item['day'] == 0) {
                $item['init']  = $this->defineIndexHour($item['day'], $item['init']);
                $item['final'] = $this->defineIndexHour($item['day'], $item['final']);
            } else if ($diff >= 0 && $item['day'] == 1) {
                $item['init']  = $this->defineIndexHour(0, $item['init']);
                $item['final'] = $this->defineIndexHour($item['day'], $item['final']);
            } else if ($diff == -1 && $item['day'] == 1) {
                $item['init']  = $this->defineIndexHour(0, $item['init']);
                $item['final'] = $this->defineIndexHour($item['day'], $item['final']);
            }
            unset($item['day']);
            return $item;
        });
    }

    private function formatMixEvent(string $date, string $timezone, $availabilityTablesEvents, $availabilityTablesNormal)
    {
        $turnEvent           = $availabilityTablesEvents['turn'];
        $dayEvent            = $turnEvent->hours_ini > $turnEvent->hours_end ? 1 : 0;
        $hourIni             = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnEvent->hours_ini, $timezone);
        $hourFin             = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $turnEvent->hours_end, $timezone)->addDay($dayEvent);
        $dayEventIni         = $hourIni->toDateString() < $hourFin->toDateString() ? 0 : $dayEvent;
        $init                = $this->defineIndexHour($dayEventIni, $hourIni->toTimeString());
        $fin                 = $this->defineIndexHour($dayEvent, $hourFin->toTimeString());
        $turnEvent['tables'] = $availabilityTablesEvents['availability'];

        $turnNewNormal = collect();
        foreach ($availabilityTablesNormal['turn'] as $index => $turnNormal) {
            $turnNormal['tables'] = $availabilityTablesNormal['availability'][$index];
            $turnNewNormal->push($turnNormal);
        };
        return ["event" => $turnEvent, "normal" => $turnNewNormal, "initE" => $init, "finE" => $fin];
    }

    public function searchPromotions(string $date, int $next_day, string $hour, int $microsite_id, string $timezone)
    {
        // return $date;
        $dayC       = Carbon::createFromFormat('Y-m-d H:i:s', $date . " " . $hour, $timezone)->addDay($next_day);
        $day        = $dayC->dayOfWeek;
        $promotions = ev_event::where('date_expire', '>=', $date)
            ->where('status', 1)
            ->where('datetime_event', '<=', $dayC->toDateTimeString())
            ->where('bs_type_event_id', $this->id_promotion)
            ->where('ms_microsite_id', $microsite_id)
            ->with(['turns' => function ($query) use ($hour, $microsite_id) {
                $query->where("hours_ini_web", '<=', $hour)->where('hours_end_web', '>=', $hour)->where('bs_type_event_id', $this->id_promotion)->where('ms_microsite_id', $microsite_id)->get();
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

    public function getReservationTemp(array $tables_id, string $date, string $hour, string $timezone, int $microsite_id, int $next_day)
    {
        $hour                = Carbon::parse($date . " " . $hour, $timezone);
        $hourActual          = Carbon::now($timezone)->subMinutes(10);
        $listReservationTemp = [];
        $tables              = collect();
        $reservations        = res_table_reservation_temp::where('date', $date)
            ->where('hour', $hour->toTimeString())
            ->where('ms_microsite_id', $microsite_id)
            ->where('next_day', $next_day)
            ->get();
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
    public function otherDay($date, $hour, $next_day, $timezone)
    {
        $dateQuery    = Carbon::parse($date . " " . $hour, $timezone)->addDay($next_day);
        $dayInitQuery = Carbon::parse($date, $timezone);
        // $fakeDate     = Carbon::create(2016, 11, 19, 02, 00, 00, $timezone);
        // Carbon::setTestNow($fakeDate);
        $dayInit = Carbon::today($timezone);
        $diff    = $dayInit->diffInDays($dayInitQuery, false);
        if ($diff == -1) {
            $dayInit = Carbon::yesterday($timezone);
        }
        $dayFin        = $dayInit->copy()->addDay();
        $dayFinNextDay = $dayInit->copy()->addDay()->addHours(5)->addMinutes(45);
        // return [$dayFin, $dateQuery, $dayFinNextDay];
        if ($next_day == 0) {
            if ($dayInit <= $dateQuery && $dateQuery < $dayFin) {
                return false;
            } else {
                return true;
            }
        } else {
            if ($dayFin <= $dateQuery && $dateQuery < $dayFinNextDay) {
                return false;
            } else {
                return true;
            }
        }
    }

    public function searchTypeEvent(string $date, int $microsite_id, string $hour, Carbon $dateCloseInit, int $next_day, string $timezone, int $idEvent)
    {
        $event = ev_event::where('status', 1)->find($idEvent);
        switch ($event->bs_type_event_id) {
            case 1:
                $eventFree = $this->checkEventFree($date, $microsite_id, $hour, $dateCloseInit, $next_day, $timezone, $idEvent);
                return $eventFree;
                break;
            case 3:
                $promotion = $this->checkEventPromo($date, $microsite_id, $hour, $dateCloseInit, $next_day, $timezone, $idEvent);
                return $promotion;
                break;
            default:
                return null;
                break;
        }
    }

    public function getDays(int $microsite_id, string $dateIni, string $dateFin, $timezone)
    {
        // $dateFake = Carbon::create(2016, 11, 29, 03, 45, null);
        // Carbon::setTestNow($dateFake);
        $dateIni = Carbon::parse($dateIni);

        $yesterday = Carbon::yesterday();
        $compare   = $yesterday->toDateTimeString() <=> $dateIni->toDateTimeString();
        $dateIni   = ($compare >= 0) ? $yesterday : $dateIni;

        $dateFin = Carbon::parse($dateFin)->addDay();

        $calendar = new Calendar(2016, 12);
        $calendar->setFixDate($dateIni, $dateFin);

        $turns = res_turn_calendar::with("turn")
            ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'start_time' => $item->start_time,
                    'end_time'   => $item->end_time,
                    'start_date' => $item->start_date,
                    'end_date'   => $item->end_date,
                ];
            });

        foreach ($turns as $turn) {
            $calendar->generateByWeekDay($turn, $turn->start_date, $turn->end_date);
        }

        $aux             = $calendar->get();
        $dateTimeClose   = collect($aux)->sortBy('date')->where('date', $yesterday->toDateString())->pop();
        $deleteYesterday = false;
        if (($dateTimeClose['start_time'] <=> $dateTimeClose['end_time']) > 0) {
            $configuration   = $this->configurationService->getConfiguration($microsite_id);
            $now             = Carbon::now()->addMinutes($configuration->time_restriction);
            $dateClose       = Carbon::parse($dateTimeClose['date'] . " " . $dateTimeClose['end_time'])->addDay();
            $deleteYesterday = $now > $dateClose ? true : false;
        } else {
            $deleteYesterday = true;
        }
        $result = collect($aux)->pluck('date')->unique()->sort();

        $deleteYesterday ? $result->shift() : false;
        return $result->values()->all();
    }

    public function getDaysDisabled(int $microsite_id, string $dateIni, string $dateFin, string $timezone)
    {
        $days    = collect($this->getDays($microsite_id, $dateIni, $dateFin, $timezone));
        $dateIni = Carbon::parse($dateIni);
        $dateFin = Carbon::parse($dateFin);
        $aux     = collect();
        while ($dateIni->toDateString() <= $dateFin->toDateString()) {
            $aux->push($dateIni->toDateString());
            $dateIni->addDay();
        }
        return $aux->diff($days)->values();
    }

    public function validNextDate(string $date, int $next_day, string $timezone)
    {
        $today     = Carbon::today($timezone);
        $dateQuery = Carbon::parse($date, $timezone);

        $compare = $today->toDateString() <=> $dateQuery->toDateString();

        if ($compare == 1 && $next_day == 0) {
            abort(500, "Hora no disponible");

        }

    }

    public function getPeople(int $microsite_id)
    {
        $configuration = $this->configurationService->getConfiguration($microsite_id);
        $maxPeople     = $configuration->max_people == 0 ? 100 : $configuration->max_people;

        $auxPeople = collect();
        for ($i = 1; $i <= $maxPeople; $i++) {
            $text = $i == 1 ? "Persona" : "Peronas";
            $auxPeople->push(["value" => $i, "text" => $i . " " . $text]);
        }
        return $auxPeople->all();
    }

    public function formatAvailability(int $microsite_id) : Array
    {
        //Function Date Actual
        $date     =  CalendarHelper::realDate($microsite_id);;
        $timezone =  $date->timezoneName;

        $dateIni = $date->copy()->firstOfMonth()->subDays(7);
        $dateFin =$date->copy()->lastOfMonth()->addDays(14);
        $next_day = 0;

        $zones        = $this->searchZones($microsite_id, $date->toDateString(), $timezone);
        $hours        = $this->getHours($microsite_id, $date->toDateString(), null, $timezone);
        try
        {
            $events       = $this->getEvents($microsite_id, $date->toDateString(), $date->toTimeString(), $timezone, $next_day, null);
        }catch(\Exception $e){
            $events = null;
        }

        $daysDisabled = $this->getDaysDisabled($microsite_id, $dateIni->toDateString(), $dateFin->toDateString(), $timezone);
        $people       = $this->getPeople($microsite_id);

        return ["people" => $people, "daysDisabled" => $daysDisabled, "events" => $events, "zones" => $zones];
    }

}
