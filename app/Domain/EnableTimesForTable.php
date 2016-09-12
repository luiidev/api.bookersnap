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
        return date("H", strtotime($time)) * 4 + (date("i", strtotime($time))) / 15;
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
            for ($i = $ini; $i < $end; $i++) {
                $this->availability[$i]['rule_id'] = $turn->res_turn_rule_id;
            }
            $index++;
            $this->defineRule($turns_table, $index);
        }
    }

}
