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

class EnableTimesForTable
{

    protected $availability = [];

    public function __construct()
    {

    }

    public function segment($turn, $turns_table = null, $rule_id = 1)
    {
        $this->availability = [];
        $this->initAvailability();
        $ini = $this->timeToIntegerRangePosition($turn->hours_ini);
        $end = $this->timeToIntegerRangePosition($turn->hours_end);

        for ($i = $ini; $i <= $end; $i++) {
            $this->availability[$i]['rule_id'] = $rule_id;
        }
        if ($turns_table) {
            $this->defineRule($turns_table);
        }
        return $this->availability;
    }

    public function reservationsTable($reservations, $tableId)
    {
        foreach ($reservations as $key => $reservation) {
            foreach ($reservation->tables as $key => $tables) {
                if ($tables->id == $tableId) {
                    $this->reservations($reservation);
                    break;
                }
            }
        }
    }

    public function blocksTable($blocks, $tableId)
    {
        foreach ($blocks as $key => $block) {
            foreach ($block->tables as $key => $tables) {
                if ($tables->id == $tableId) {
                    $this->blocks($block);
                    break;
                }
            }
        }
    }

    /*
     * Retorna el numero de segmento de 15 min de las  120 min = 30 horas.
     */

    private function defineIndex(string $date, string $datetime)
    {
        list($dateIni, $hoursIni) = explode(" ", $datetime);
        list($hours, $minute)     = explode(":", $hoursIni);

        $r = $minute % 15;
        if ($r > 0) {
            $minute -= $r;
            if ($r > 8) {
                $minute += 15;
            }
        }
        $index = (int) $hours * 4 + (int) $minute / 15;
        if (($dateIni <=> $date) == 1) {
            $index += 96;
        }
        return $index;
    }

    private function reservations($reservation)
    {
        $ini = $this->defineIndex($reservation->date_reservation, $reservation->datetime_input);
        $end = $this->defineIndex($reservation->date_reservation, $reservation->datetime_output);

        for ($i = $ini; $i < $end; $i++) {
            /*$this->availability[$i]['ini']            = $startHour;
            $this->availability[$i]['end']            = $endHour;*/
            $this->availability[$i]['reservations'][] = ["id" => $reservation->id];
            $this->availability[$i]['reserva']        = true;
            $this->availability[$i]['rule_id']        = 0;
        }
    }

    private function blocks($block)
    {
//        if(($block->start_time <=> $block->start_time) == 1){
        //
        //        }
        $ini = $this->defineIndex($block->start_date);
        $end = $this->defineIndex($block->start_date);

        for ($i = $ini; $i <= $end; $i++) {
            $this->availability[$i]['ini']      = $ini;
            $this->availability[$i]['end']      = $end;
            $this->availability[$i]['blocks'][] = $block;
            $this->availability[$i]['block']    = true;
            $this->availability[$i]['rule_id']  = 0;
        }
    }

    public function getAvailability()
    {
        return $this->availability;
    }

    private function initAvailability()
    {
        for ($i = 0; $i < 120; $i++) {
            $nextday              = ($i < 96) ? 0 : 1;
            $this->availability[] = array(
                "time"    => $this->rangeToTime($i),
                "index"   => $i,
                "rule_id" => -1,
                "nextday" => $nextday,
                "reserva" => false,
            );
        }
    }

    public function disabled()
    {
        unset($this->availability);
        $this->initAvailability();
        return $this->availability;
    }

    /*
     * Retorna el numero de segmento de 15 min de las  120 min = 30 horas.
     */

    private function timeToIntegerRangePosition(string $time)
    {
//        $minute = (date("i", strtotime($time)));
        //        $r      = $minute % 15;
        //        if ($r > 0) {
        //            $minute -= $r;
        //            if ($r > 8) {
        //                $minute += 15;
        //            }
        //        }
        //        return date("H", strtotime($time)) * 4 + $minute / 15;

        list($hours, $minute) = explode(":", $time);

        $r = $minute % 15;
        if ($r > 0) {
            $minute -= $r;
            if ($r > 8) {
                $minute += 15;
            }
        }
        $index = (int) $hours * 4 + (int) $minute / 15;

        return $index;

    }

    private function rangeToTime(int $index)
    {
        if ($index >= 96) {
            $index = $index - 96;
        }
        $hora    = str_pad((int) ($index / 4), 2, "0=", STR_PAD_LEFT);
        $minutos = str_pad(($index % 4) * 15, 2, "0=", STR_PAD_LEFT);
        return $hora . ":" . $minutos . ":00";
    }

    /*
     * Funcion recursiva para asignacion de tipo de avilitacion segun su intervalo de tiempo.
     */

    private function defineRule($turns_table, $index = 0)
    {
        if (count($turns_table) > 0 && @$turns_table[$index]) {
            $turn = $turns_table[$index];
            $ini  = $this->timeToIntegerRangePosition($turn->start_time);
            $end  = $this->timeToIntegerRangePosition($turn->end_time);
            if ($turn->next_day == 1 && $ini < $end) {
                $ini = $ini + 96;
                $end = $end + 96;
            } else if ($end < $ini) {
                $end = $end + 96;
            }
            for ($i = $ini; $i <= $end; $i++) {
                $this->availability[$i]['rule_id'] = $turn->res_turn_rule_id;
                //$this->availability[$i]['rule'][]  = $turn;
            }
            $index++;
            $this->defineRule($turns_table, $index);
        }
    }

}
