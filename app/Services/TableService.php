<?php

namespace App\Services;

use App\Domain\EnableTimesForTable;
use App\Entities\Block;
use App\Entities\ev_event;
use App\res_reservation;
use App\res_table;
use App\Services\CalendarService;

class TableService
{

    protected $_CalendarService;

    public function __construct(CalendarService $CalendarService)
    {
        $this->_CalendarService = $CalendarService;
    }

    /*
     * Obtener turnos del calendario de una fecha
     *
     */

    public function turnsCalendarByDate(int $microsite_id, string $date)
    {
        return res_table::where("status", 1)->with(["zone.turns.turnCalendar" => function ($query) use ($date) {
            return $query->where("start_date", "<=", $date)
                ->where("end_date", ">=", $date)
                ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($date));
        }, "zone.turns.turnTable"])->get();
    }

    public function reservationsByDate(int $microsite_id, string $date)
    {
        return res_reservation::with("tables")->where("ms_microsite_id", $microsite_id)->where("date_reservation", $date)->where(function ($query) {
            return $query->where('res_reservation_status_id', '<=', 4)->where('wait_list', 0)->orWhere(function ($query) {
                return $query->where('wait_list', "=", 1)->where('res_reservation_status_id', 4);
            });
        })->get();
    }

    public function eventsFreeByDate(int $microsite_id, string $date)
    {
        return ev_event::with("turn.zones.tables")->where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 1)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function eventsPayByDate(int $microsite_id, string $date)
    {
        return ev_event::where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 2)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function promotionsFreeByDate(int $microsite_id, string $date)
    {
        return ev_event::with("turns")->where("ms_microsite_id", $microsite_id)->where("bs_type_event_id", 3)->whereRaw("DATE_FORMAT(datetime_event, '%Y-%m-%d') = ?", [$date])->get();
    }

    public function blocksByDate(int $microsite_id, string $date)
    {
        return Block::with("tables")->where("ms_microsite_id", $microsite_id)->where("start_date", $date)->get();
    }

    protected function testReturnEvents(int $microsite_id, string $date)
    {
        $eventsFree     = $this->eventsFreeByDate($microsite_id, $date);
        $eventsPay      = $this->eventsPayByDate($microsite_id, $date);
        $promotionsFree = $this->promotionsFreeByDate($microsite_id, $date);
        return [
            "EventsFree" => $eventsFree,
            "EventsPay"  => $eventsPay,
            "Promotions" => $promotionsFree,
        ];
    }

    public function availability(int $microsite_id, string $date)
    {

//        return $this->testReturnEvents($microsite_id, $date);
        $tables       = $this->turnsCalendarByDate($microsite_id, $date);
        $reservations = $this->reservationsByDate($microsite_id, $date);
        $blocks       = $this->blocksByDate($microsite_id, $date);
//        $eventsFree = $this->eventsFreeByDate($microsite_id, $date);
        //        $eventsPay = $this->eventsPayByDate($microsite_id, $date);
        //        $promotionsFree = $this->promotionsFreeByDate($microsite_id, $date);

        $newTables           = [];
        $tempTable           = null;
        $EnableTimesForTable = new EnableTimesForTable();

        foreach ($tables as $key => $table) {
            $tempTable = (object) [
                "id"          => $table->id,
                "res_zone_id" => $table->res_zone_id,
                "name"        => $table->name,
                "min_cover"   => $table->min_cover,
                "max_cover"   => $table->max_cover,
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
                    $rs        = $EnableTimesForTable->segment($turn, $turnsTable);
                    $existTurn = true;
                }
            }

            if ($existTurn) {
                //$EnableTimesForTable->reservationsTable($reservations, $table->id);
                //$EnableTimesForTable->blocksTable($blocks, $table->id);
                $newTables[] = [
                    "id"           => $table->id,
                    "res_zone_id"  => $table->res_zone_id,
                    "name"         => $table->name,
                    "min_cover"    => $table->min_cover,
                    "max_cover"    => $table->max_cover,
                    "availability" => $EnableTimesForTable->getAvailability(),
                ];
            }
        }

        return $newTables;
    }

}
