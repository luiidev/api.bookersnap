<?php

namespace App\Services;

use App\Domain\EnableTimesForTable;
use App\Entities\Block;
use App\Entities\ev_event;
use App\res_reservation;
use App\res_table;
use App\Services\CalendarService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TableService {

    protected $_CalendarService;
    protected $_DATETIME_OPEN;
    protected $_DATETIME_CLOSE;
    protected $_TURNS_CALENDAR;
    protected $_TURNS_EVENT;
    protected $_EVENTS;

    public function __construct(CalendarService $CalendarService) {
        $this->_CalendarService = $CalendarService;
    }

    /*
     * Obtener turnos del calendario de una fecha
     *
     */

    public function turnsCalendarByDate(int $microsite_id, string $date) {
        return res_table::where("status", 1)->with(["zone.turns.turnCalendar" => function ($query) use ($date) {
                        return $query->where("start_date", "<=", $date)
                                        ->where("end_date", ">=", $date)
                                        ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($date));
                    }, "zone.turns.turnTable"])->get();
    }

    public function reservationsByDate(int $microsite_id, string $date) {
        return res_reservation::select(array('id', 'date_reservation', 'hours_reservation', 'next_day', 'hours_duration', 'res_reservation_status_id', 'datetime_input', 'datetime_output'))->with(["tables" => function($query) {
                        return $query->select('res_table.id');
                    }])->where("ms_microsite_id", $microsite_id)->where("date_reservation", $date)->where(function ($query) {
                    return $query->where('res_reservation_status_id', '<=', 4)->where('wait_list', 0)->orWhere(function ($query) {
                                return $query->where('wait_list', "=", 1)->where('res_reservation_status_id', 4);
                            });
                })->get();
    }

    public function eventsFreeByDate(int $microsite_id, string $date) {
        return ev_event::with("turn.zones.tables")->where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 1)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function eventsPayByDate(int $microsite_id, string $date) {
        return ev_event::where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 2)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function promotionsFreeByDate(int $microsite_id, string $date) {
        return ev_event::with("turns")->where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 3)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function blocksByDate(int $microsite_id, string $date) {
        return Block::select(array('id', 'start_date', 'start_time', 'end_time', 'next_day'))->with(["tables" => function($query) {
                        return $query->select('res_table.id');
                    }])->where("ms_microsite_id", $microsite_id)->where("start_date", $date)->get();
    }

    protected function testReturnEvents(int $microsite_id, string $date) {
        $eventsFree = $this->eventsFreeByDate($microsite_id, $date);
        $eventsPay = $this->eventsPayByDate($microsite_id, $date);
        $promotionsFree = $this->promotionsFreeByDate($microsite_id, $date);
        return [
            "EventsFree" => $eventsFree,
            "EventsPay" => $eventsPay,
            "Promotions" => $promotionsFree,
        ];
    }

    public function availability(int $microsite_id, string $date) {

//        return $this->testReturnEvents($microsite_id, $date);
        $tables = $this->turnsCalendarByDate($microsite_id, $date);
//        $reservations = $this->reservationsByDate($microsite_id, $date);
//        $blocks = $this->blocksByDate($microsite_id, $date);
//        $eventsFree = $this->eventsFreeByDate($microsite_id, $date);
        //        $eventsPay = $this->eventsPayByDate($microsite_id, $date);
        //        $promotionsFree = $this->promotionsFreeByDate($microsite_id, $date);

        $newTables = [];
        $tempTable = null;
        $EnableTimesForTable = new EnableTimesForTable();

        foreach ($tables as $key => $table) {
            $tempTable = (object) [
                        "id" => $table->id,
                        "res_zone_id" => $table->res_zone_id,
                        "name" => $table->name,
                        "min_cover" => $table->min_cover,
                        "max_cover" => $table->max_cover,
            ];
            $existTurn = false;

            $EnableTimesForTable->disabled();

            foreach ($table->zone->turns as $turn) {
                if (collect($turn->turnCalendar)->count() > 0) {
                    $turnsTable = [];
                    foreach ($turn->turnTable as $key => $value) {
                        if ($value->res_table_id == $table->id) {
                            $turnsTable[] = $value;
                        }
                    }
                    $rs = $EnableTimesForTable->segment($turn, $turnsTable);
                    $existTurn = true;
                }
            }

            if ($existTurn) {
                //$EnableTimesForTable->reservationsTable($reservations, $table->id);
                //$EnableTimesForTable->blocksTable($blocks, $table->id);
                $newTables[] = [
                    "id" => $table->id,
                    "res_zone_id" => $table->res_zone_id,
                    "name" => $table->name,
                    "min_cover" => $table->min_cover,
                    "max_cover" => $table->max_cover,
                    "availability" => $EnableTimesForTable->getAvailability(),
                ];
            }
        }

        return $newTables;
    }

    public function turnsCalendarByDateOk(int $microsite_id, string $date) {
        return res_table::where("status", 1)->with(["zone.turns.turnCalendar" => function ($query) use ($date) {
                        return $query->where("start_date", "<=", $date)
                                        ->where("end_date", ">=", $date)
                                        ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($date));
                    }, "zone.turns.turnTable"])->get();
    }

    public function turnEvent($microsite_id, $date) {

        $fecha = Carbon::parse($date);
        $datenow = $fecha->toDateString();
        $dayOfWeek = $fecha->dayOfWeek + 1;
        $nextdate = $fecha->copy()->addDay()->toDateString();

        $turnsEnd = \App\res_turn_calendar::select('res_turn.id', DB::raw("IF(res_turn.hours_ini > res_turn.hours_end, CONCAT('$nextdate', ' ', res_turn.hours_end), CONCAT('$datenow', ' ', res_turn.hours_end)) as  end_datetime"))
                ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                ->where("res_turn.ms_microsite_id", $microsite_id)
                ->where("start_date", "<=", $datenow)
                ->where("end_date", ">=", $datenow)
                ->orderBy('start_date', 'desc')
                ->first();
        $endDate = ($turnsEnd) ? $turnsEnd->end_datetime : $datenow . " 23:59:59";

        return ev_event::with(["turn" => function($query) use ($datenow, $nextdate) {
                                $query->select('id', 'hours_ini', 'hours_end', 'name', DB::raw("CONCAT('$datenow', ' ', res_turn.hours_ini) as  start_datetime"), DB::raw("IF(res_turn.hours_ini > res_turn.hours_end, CONCAT('$nextdate', ' ', res_turn.hours_end), CONCAT('$datenow', ' ', res_turn.hours_end)) as  end_datetime"));
                            }])->where('status', 1)
                        ->where('datetime_event', '>=', $fecha->toDateString() . " 00:00:00")
                        ->where('datetime_event', '<', $endDate)
                        ->where('bs_type_event_id', 1)
                        ->where('ms_microsite_id', $microsite_id)
                        ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
                        ->get()->map(function($item) use ($datenow, $nextdate) {
                    $turn = $item->turn;
                    $turn->event_id = $item->id;
                    return $turn;
                });
    }

    private function zonesAvailabilityByTurns($turnsIds) {

        /* Obtener Los Ids de las zonas Habbilitadas por los turnos habiles */
        $turnZoneIds = \App\res_turn_zone::whereIn('res_turn_id', $turnsIds)->groupBy('res_zone_id')->pluck('res_zone_id');

//        $zones = \App\res_zone::whereIn('res_zone.id', $turnZoneIds)->where("res_zone.status", 1)->with(['tables.turns' => function($query) use($turnsIds) {
//                        return $query->whereIn('res_turn_id', $turnsIds)->where('res_turn_rule_id', 2);
//                    }, 'turnZone' => function($query) use($turnsIds) {
//                        return $query->whereIn('res_turn_id', $turnsIds)->with('turn');
//                    }, 'tables.reservations' => function($query) use($fecha) {
//                        return $query->select(array('res_reservation.id', 'res_reservation.date_reservation', 'res_reservation.hours_reservation', 'res_reservation.next_day', 'res_reservation.hours_duration', 'res_reservation.res_reservation_status_id', 'res_reservation.datetime_input', 'res_reservation.datetime_output'))->where('res_reservation.date_reservation', $fecha->toDateString());
//                    }, 'tables.blocks' => function($query) use($fecha) {
//                        return $query->select(array('res_block.id', 'res_block.start_date', 'res_block.start_time', 'res_block.end_time', 'res_block.next_day'))->where('res_block.start_date', $fecha->toDateString());
//                    }])->get();

        $zones = \App\res_zone::whereIn('res_zone.id', $turnZoneIds)->where("res_zone.status", 1)->with(['tables' => function($query) use($turnsIds) {
                        return $query->where('status', 1)->with(['turns' => function($query) use ($turnsIds) {
                                        return $query->whereIn('res_turn_id', $turnsIds)->where('res_turn_rule_id', 2);
                                    }]);
                    }, 'turnZone' => function($query) use($turnsIds) {
                        return $query->whereIn('res_turn_id', $turnsIds)->with('turn');
                    }])->get();

        return $zones;
    }

    private function zoneAvailabilityByTruns($zoneId, $turnsIds) {

        $zone = \App\res_zone::whereIn('res_zone.id', $turnZoneIds)->where("res_zone.status", 1)->with(['tables.turns' => function($query) use($turnsIds) {
                        return $query->whereIn('res_turn_id', $turnsIds)->where('res_turn_rule_id', 2);
                    }, 'turnZone' => function($query) use($turnsIds) {
                        return $query->where('res_turn_id', $zoneId)->with('turn');
                    }])->first();

        return $zone;
    }

    /**
     * Obtener Los Ids de los turnos Habilitados para la fecha del mcalendario
     * @param int $microsite_id
     * @param string $date
     * @return array
     */
    private function turnsIdsByDateCalendar(int $microsite_id, string $date) {
        $fecha = Carbon::parse($date);
        $dayOfWeek = $fecha->dayOfWeek + 1;

        $queryTurns = \App\res_turn_calendar::select('res_turn.*')->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                ->where("res_turn.ms_microsite_id", $microsite_id)
                ->where("start_date", "<=", $fecha->toDateString())
                ->where("end_date", ">=", $fecha->toDateString())
                ->orderBy('start_date', 'desc');
        $turnsIds = $queryTurns->pluck('id');
        return $turnsIds;
    }

    public function searchAvailability($microsite_id, $num_guest, $date, $hour) {
        $turnsIds = $this->turnsIdsByDateCalendar($microsite_id, $date);
//        $realdate = Helpers\CalendarHelper::realDateByHousInDate($microsite_id, $date, $hour);
        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        $turnEvent = $this->turnEvent($microsite_id, $date);
        $zones = $this->zonesAvailabilityByTurns($turnsIds);
        return $this->searchAvailavilityByZone($zones[0], $turnEvent);
    }

    public function tablesZoneAvailability($microsite_id, $date, $zoneId) {
        $turnsIds = $this->turnsIdsByDateCalendar($microsite_id, $date);
        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        $turnEvent = $this->turnEvent($microsite_id, $date);
        $zones = $this->zoneAvailabilityByTruns($zoneId, $turnsIds);
        return $this->searchAvailavilityByZone($zones, $turnEvent);
    }

    /**
     * Disponibilidad de todas las mesas en una fecha
     * @param type $microsite_id
     * @param type $date
     * @return type
     */
    public function tablesAvailability($microsite_id, $date) {
        $turnsIds = $this->turnsIdsByDateCalendar($microsite_id, $date);
        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        $turnEvent = $this->turnEvent($microsite_id, $date);
        $zones = $this->zonesAvailabilityByTurns($turnsIds);

        $newTables = collect();
        foreach ($zones as $zone) {
            $this->initAvailavilityTable();
            $this->enableTurnZone($zone->turnZone);
            foreach ($zone->tables as $table) {
                $this->enableTurnTable($table->turns);
                $this->turnEventsAvailability($turnEvent);
                $newTables->push([
                    "id" => $table->id,
                    "res_zone_id" => $table->res_zone_id,
                    "name" => $table->name,
                    "min_cover" => $table->min_cover,
                    "max_cover" => $table->max_cover,
                    "availability" => $this->getAvailavilityTable(),
                ]);
            }
        }
        return $newTables;
    }

    protected $availavilityTable;
    protected $_ID_RESERVATION_WEB = 2;

    private function indexToTime($index) {
        $index = ($index < 96) ? $index : $index - 96;
        $hora = str_pad((int) ($index / 4), 2, "0=", STR_PAD_LEFT);
        $minutos = str_pad(($index % 4) * 15, 2, "0=", STR_PAD_LEFT);
        return $hora . ":" . $minutos . ":00";
    }

    private function timeToIndex($time) {
        list($hours, $minute) = explode(":", $time);
        $r = $minute % 15;
        if ($r > 0) {
            $minute -= $r;
            if ($r > 8) {
                $minute += 15;
            }
        }
        return (int) $hours * 4 + (int) $minute / 15;
    }

    private function defineIndex($date, $datetime) {
        list($dateIni, $hoursIni) = explode(" ", $datetime);
        list($hours, $minute) = explode(":", $hoursIni);

        $r = $minute % 15;
        if ($r > 0) {
            $minute -= $r;
            if ($r > 8) {
                $minute += 15;
            }
        }
        $index = (int) $hours * 4 + (int) $minute / 15;
        return (($dateIni <=> $date) == 1) ? $index + 96 : $index;
    }

    public function getAvailavilityTable() {
        return $this->availavilityTable;
    }

    public function initAvailavilityTable() {
        $this->availavilityTable = [];
        for ($i = 0; $i < 120; $i++) {

            $nextday = ($i < 96) ? 0 : 1;

            $this->availavilityTable[] = [
                "time" => $this->indexToTime($i),
                "index" => $i,
                "today" => ($i < 96),
                "nextday" => $nextday,
                "rule_id" => -1,
                "turn" => false,
                "availability" => false,
                "reserva" => false,
                'event_id' => null
            ];
        }
    }

    public function enableTurnZone($turnsZone) {
        foreach ($turnsZone as $turnzone) {
            if ($turnzone->turn) {
                $turn = $turnzone->turn;
                $ini = $this->timeToIndex($turn->hours_ini);
                $end = $this->timeToIndex($turn->hours_end);
                $end = ($ini > $end) ? $end + 96 : $end;
                for ($i = $ini; $i < $end; $i++) {
                    $this->availavilityTable[$i]['rule_id'] = $turnzone->res_turn_rule_id;
                    $this->availavilityTable[$i]['turn'] = true;
                    if ($turn->res_type_turn_id == $this->_ID_RESERVATION_WEB) {
                        $this->availavilityTable[$i]['availability'] = true;
                    }
                }
            }
        }
    }

    public function enableTurnTable($turnsTable) {

        foreach ($turnsTable as $turn) {
            $ini = $this->timeToIndex($turn->start_time);
            $end = $this->timeToIndex($turn->end_time);
            $end = ($ini > $end || $turn->next_day) ? $end + 96 : $end;
            for ($i = $ini; $i < $end; $i++) {
                if (@$this->availavilityTable[$i]['rule_id'] === true) {
                    $this->availavilityTable[$i]['rule_id'] = $turn->res_turn_rule_id;
                    if ($turn->res_turn_rule_id == $this->_ID_RESERVATION_WEB) {
                        $this->availavilityTable[$i]['availability'] = true;
                    } else {
                        $this->availavilityTable[$i]['availability'] = false;
                    }
                }
            }
        }
    }

    private function turnEventsAvailability($turnsEvent) {

        foreach ($turnsEvent as $turn) {
            $ini = $this->timeToIndex($turn->hours_ini);
            $end = $this->timeToIndex($turn->hours_end);
            $end = ($ini > $end) ? $end + 96 : $end;

            for ($i = $ini; $i < $end; $i++) {
                /* $this->availability[$i]['ini']            = $startHour;
                  $this->availability[$i]['end']            = $endHour; */
                if (@$this->availavilityTable[$i]['rule_id'] > 0) {
                    $this->availavilityTable[$i]['rule_id'] = 2;
                    $this->availavilityTable[$i]['event_id'] = $turn->event_id;
                    $this->availavilityTable[$i]['availability'] = false;
                }
            }
        }
    }

    public function searchAvailavilityByZone($zone, $turnEvent) {

        $newTables = collect();

        $this->initAvailavilityTable();
        $this->enableTurnZone($zone->turnZone);
        foreach ($zone->tables as $table) {
            //$this->enableTurnTable($table->turns);
            //$EnableTimesForTable->reservationsTable($reservations, $table->id);
            //$EnableTimesForTable->blocksTable($blocks, $table->id);
            $this->turnEventsAvailability($turnEvent);
            $newTables->push([
                "id" => $table->id,
                "res_zone_id" => $table->res_zone_id,
                "name" => $table->name,
                "min_cover" => $table->min_cover,
                "max_cover" => $table->max_cover,
                "availability" => $this->getAvailavilityTable(),
            ]);
        }

        return $newTables;
    }

}
