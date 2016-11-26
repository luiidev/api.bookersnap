<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Domain;

/**
 * Description of EnableTimesForTable
 *
 * @author USER
 */
use Carbon\Carbon;

class EnableTimesForTable {

    protected $availability = [];

    public function __construct() {
        
    }

    public function segment($turn, $turns_table) {
        $this->availability = [];
        $this->initAvailability();
        $ini = $this->timeToIntegerRangePosition($turn->hours_ini);
        $end = $this->timeToIntegerRangePosition($turn->hours_end);
        for ($i = $ini; $i <= $end; $i++) {
            $this->availability[$i]['rule_id'] = 1;
        }
        $this->defineRule($turns_table);
        return $this->availability;
    }

    public function reservationsTable($reservations, $tableId) {
        foreach ($reservations as $key => $reservation) {
            foreach ($reservation->tables as $key => $tables) {
                if ($tables->id = $tableId) {
                    $this->reservations($reservation);
                    break;
                }
            }
        }
    }
    
    public function blocksTable($blocks, $tableId) {
        foreach ($blocks as $key => $block) {
            foreach ($block->tables as $key => $tables) {
                if ($tables->id = $tableId) {
                    $this->blocks($block);
                    break;
                }
            }
        }
    }
    
    protected function reservations($reservation) {
        list($year, $month, $day) = $reservation->date_reservation;
        list($h, $m, $s) = $reservation->hours_reservation;
        list($hd, $md, $sd) = $reservation->hours_duration;
        $startHour                = Carbon::now()->addHours($h)->addMinutes($m)->addSeconds($s);
        $endHour                  = $startHour->addHours($hd)->addMinutes($md)->addSeconds($sd);
        
        $ini = $this->timeToIntegerRangePosition($reservation->hours_reservation);
        $end = $this->timeToIntegerRangePosition($endHour->format("H:m:s"));
        
        for ($i = $ini; $i <= $end; $i++) {
//            $this->availability[$i]['ini'] = $startHour->format("H:m:s");
//            $this->availability[$i]['end'] = $endHour->format("H:m:s");
//            $this->availability[$i]['reservations'][] = $reservation->id;
            $this->availability[$i]['reserved'] = true;
            $this->availability[$i]['rule_id'] = 0;
        }
    }
    
    protected function blocks($block) {     
        $ini = $this->timeToIntegerRangePosition($block->start_time);
        $end = $this->timeToIntegerRangePosition($block->end_time);
        
        for ($i = $ini; $i <= $end; $i++) {
//            $this->availability[$i]['ini'] = $ini;
//            $this->availability[$i]['end'] = $end;
//            $this->availability[$i]['block'][] = $block->id;
            $this->availability[$i]['rule_id'] = 0;
        }
    }

    public function getAvailability() {
        return $this->availability;
    }

    private function initAvailability() {
        for ($i = 0; $i < 120; $i++) {
            $nextday = ($i < 96) ? 0 : 1;
            $this->availability[] = array(
                "time" => $this->rangeToTime($i),
                "rule_id" => -1,
                "nextday" => $nextday
            );
        }
    }

    public function disabled() {
        $this->availability = [];
        $this->initAvailability();
        return $this->availability;
    }

    /*
     * Retorna el numero de segmento de 15 min de las  120 min = 30 horas.
     */

    private function timeToIntegerRangePosition(string $time) {
        $minute = (date("i", strtotime($time)));
        $r = $minute%15;
        if($r > 0){
            $minute -= $r; 
            if($r > 8){
                $minute += 15;
            }
        }
        return date("H", strtotime($time)) * 4 + $minute / 15;
    }

    private function rangeToTime($index) {
        return date("H:i:s", $index * 60 * 15);
    }

    /*
     * Funcion recursiva para asignacion de tipo de avilitacion segun su intervalo de tiempo.
     */

    private function defineRule($turns_table, $index = 0) {
        if (count($turns_table) > 0 && @$turns_table[$index]) {
            $turn = $turns_table[$index];
            $ini = $this->timeToIntegerRangePosition($turn->start_time);
            $end = $this->timeToIntegerRangePosition($turn->end_time);
            if ($turn->next_day == 1 && $ini < $end) {
                $ini = $ini + 96;
                $end = $end + 96;
            } else if ($end < $ini) {
                $end = $end + 96;
            }
            for ($i = $ini; $i <= $end; $i++) {
                $this->availability[$i]['rule_id'] = $turn->res_turn_rule_id;
            }
            $index++;
            $this->defineRule($turns_table, $index);
        }
    }

}
