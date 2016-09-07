<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App\Services\Helpers;
/**
 * Description of TurnServiceHelper
 *
 * @author USER
 */
class TurnServiceHelper {    
    
    public function createTurnTable(array $time_range, string $start_time, string $end_time, int $turn_id, int $table_id) {

        $time_ini = \App\Domain\TimeForTable::timeToIndex($start_time);
        $time_end = \App\Domain\TimeForTable::timeToIndex($end_time);

        $rowsTurnTable = [];
        $new_rule = -1;
        $turnTable = [
            "res_turn_id" => $turn_id,
            "res_table_id" => $table_id,
        ];
        
        for ($i = $time_ini; $i < $time_end; $i++) {

            $rule_id = $this->getRuleId($time_range, $i);
            $rule_id_old = $this->getRuleId($time_range, $i - 1);
            $rule_id_next = $this->getRuleId($time_range, $i + 1);
            
            if ($rule_id_old != $rule_id || $i == $time_ini) {
                $turnTable['start_time'] = \App\Domain\TimeForTable::indexToTime($i);
                $turnTable['end_time'] = $turnTable['start_time'];
                $turnTable['res_turn_rule_id'] = ($rule_id != -1) ? $rule_id : 0;
            } else if ($rule_id_old == $rule_id) {
                $turnTable['end_time'] = \App\Domain\TimeForTable::indexToTime($i);
            }

            if ($rule_id_next != $rule_id || $i == $time_end - 1) {
                $rowsTurnTable[] = $turnTable;
            }
        }
        return $rowsTurnTable;
    }

    public function getRuleId(array $data, int $index) {
        if (is_array($data) && isset($data[$index]) && is_array($data[$index]) && isset($data[$index]["rule_id"])) {
            return $data[$index]["rule_id"];
        }
        return -1;
    }
}
