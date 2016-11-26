<?php

namespace App\Services;

use App\res_table;
use App\res_turn;
use App\res_turn_calendar;
use App\Domain\EnableTimesForTable;
use App\Services\CalendarService;
use App\res_reservation;
use App\Entities\Block;

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
        return res_table::with(["zone.turns.turnCalendar" => function($query) use ($date) {
                        return $query->where("start_date", "<", $date)
                                        ->where("end_date", ">", $date)
                                        ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($date));
                    }, "zone.turns.turnTable"])->get();
    }

    public function reservationsByDate(int $microsite_id, string $date) {
        return res_reservation::with("tables")->where("ms_microsite_id", $microsite_id)->where("date_reservation", $date)->where(function($query){
            return $query->where('res_reservation_status_id', '<=', 4)->where('wait_list', 0)->orWhere(function($query){
                return $query->where('wait_list', "=", 1)->where('res_reservation_status_id', 4);
            });
        })->get();
    }
    
    public function blocksByDate(int $microsite_id, string $date) {
        return Block::with("tables")->where("ms_microsite_id", $microsite_id)->where("start_date", $date)->get();
    }

    public function availability(int $microsite_id, string $date) {

        $tables = $this->turnsCalendarByDate($microsite_id, $date);
        $reservations = $this->reservationsByDate($microsite_id, $date);
        $blocks = $this->blocksByDate($microsite_id, $date);
        $newTables = [];

        $tempTable = null;
        $EnableTimesForTable = new EnableTimesForTable();
        foreach ($tables as $key => $table) {

            $tempTable = (object) [
                        "id" => $table->id,
                        "res_zone_id" => $table->res_zone_id,
                        "name" => $table->name,
                        "min_cover" => $table->min_cover,
                        "max_cover" => $table->max_cover
            ];
            $existTurn = false;

            $EnableTimesForTable->disabled();

            foreach ($table->zone->turns as $turn) {
                if (collect($turn->turnCalendar)->count() > 0) {
                    $rs = $EnableTimesForTable->segment($turn, $turn->turnTable);
                    $existTurn = true;
                }
            }
            
            if ($existTurn) {                
                $EnableTimesForTable->reservationsTable($reservations, $table->id);
                $EnableTimesForTable->blocksTable($blocks, $table->id);
                $newTables[] = [
                    "id" => $table->id,
                    "res_zone_id" => $table->res_zone_id,
                    "name" => $table->name,
                    "min_cover" => $table->min_cover,
                    "max_cover" => $table->max_cover,
                    "availability" => $EnableTimesForTable->getAvailability()
                ];
            }            
        }
        
        return $newTables;
    }

}
