<?php

namespace App\Services;

use App\Domain\EnableTimesForTable;
use App\Entities\Block;
use App\Entities\ev_event;
use App\res_reservation;
use App\res_table;
use App\Services\CalendarService;
use Illuminate\Support\Facades\DB;

class TableService {

    protected $_CalendarService;

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
        return res_reservation::select(array('id', 'date_reservation', 'hours_reservation', 'next_day', 'hours_duration', 'res_reservation_status_id', 'datetime_input', 'datetime_output'))->with(["tables" => function($query){
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
        return Block::select(array('id', 'start_date', 'start_time', 'end_time', 'next_day'))->with(["tables" => function($query){
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
        $reservations = $this->reservationsByDate($microsite_id, $date);
        $blocks = $this->blocksByDate($microsite_id, $date);
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
    
    public function searchAvailability($microsite_id, $num_guest, $date, $hour) {

        $fecha = \Carbon\Carbon::parse($date . " " . $hour);
        $dayOfWeek = $fecha->dayOfWeek + 1;
        
        $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                ->where("res_turn.ms_microsite_id", $microsite_id)
                ->where("start_date", "<=", $fecha->toDateString())
                ->where("end_date", ">=", $fecha->toDateString())
                ->pluck('id');
        
        return $zones = \App\res_zone::where('res_zone.ms_microsite_id', $microsite_id)->where("res_zone.status", 1)->with(['tables.turns' => function($query) use($turnsIds){
            return $query->whereIn('res_turn_id', $turnsIds);
        }, 'turns' => function($query) use($turnsIds){
            return $query->whereIn('res_turn_id', $turnsIds);
        }, 'tables.reservations' => function($query) use($fecha){
            return $query->select(array('res_reservation.id', 'res_reservation.date_reservation', 'res_reservation.hours_reservation', 'res_reservation.next_day', 'res_reservation.hours_duration', 'res_reservation.res_reservation_status_id', 'res_reservation.datetime_input', 'res_reservation.datetime_output'))->where('res_reservation.date_reservation', $fecha->toDateString());
        }, 'tables.blocks'=> function($query) use($fecha){
            return $query->select(array('res_block.id', 'res_block.start_date', 'res_block.start_time', 'res_block.end_time', 'res_block.next_day'))->where('res_block.start_date', $fecha->toDateString());
        }])->get();
        
                    
        $tables = $this->turnsCalendarByDate($microsite_id, $date);
        $reservations = $this->reservationsByDate($microsite_id, $date);
        $blocks = $this->blocksByDate($microsite_id, $date);
//        $eventsFree = $this->eventsFreeByDate($microsite_id, $date);
        //        $eventsPay = $this->eventsPayByDate($microsite_id, $date);
        //        $promotionsFree = $this->promotionsFreeByDate($microsite_id, $date);

        $newTables = collect();
        $tempTable = null;
        
        
        foreach ($tables as $key => $table) {            
            
            $existTurn = false;

//            $EnableTimesForTable->disabled();
            $EnableTimesForTable = new EnableTimesForTable();
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
                $EnableTimesForTable->reservationsTable($reservations, $table->id);
                //$EnableTimesForTable->blocksTable($blocks, $table->id);
                $newTables->push([
                    "id" => $table->id,
                    "res_zone_id" => $table->res_zone_id,
                    "name" => $table->name,
                    "min_cover" => $table->min_cover,
                    "max_cover" => $table->max_cover,
                    "availability" => $EnableTimesForTable->getAvailability(),
                ]);
            }
        }

        return $newTables;
    }

}
