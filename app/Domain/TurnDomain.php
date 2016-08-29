<?php

namespace App\Domain;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TurnDomain
 *
 * @author USER
 */
use App\res_day_turn_zone;
use Illuminate\Database\Eloquent\Collection;

class TurnDomain {

    /**
     * lista de todos los turnos de una zona.
     * @param  array    $daysUse   coleccion App\res_day_turn_zone en uso.
     * @return array    Estructura de dias disponibles para una zona
     */
    public function availableDays(Collection $daysTypeturn) {
        $daysAvailable = array();
        for ($i = 0; $i < 7; $i++) {
            if (!$this->existDay($daysTypeturn, $i)) {
                $res_day_turn_zone = new res_day_turn_zone();
                $res_day_turn_zone->day = $i;
                $daysAvailable[] = $res_day_turn_zone;
            }
        }
        return $daysAvailable;
    }

    private function existDay(Collection $daysTypeturn, int $day) {
        foreach ($daysTypeturn as $res_day_turn_zone) {
            if ($res_day_turn_zone instanceof res_day_turn_zone) {
                if ($res_day_turn_zone->day == $day) {
                    return true;
                }
            }
        }
        return false;
    }

    public function tablesAvailability($turn, $tables, $tables_availability) {
        $ini = (date("H", strtotime($turn->hours_ini)) * 1 + (date("i", strtotime($turn->hours_ini))) / 15) * 4;
        $end = (date("H", strtotime($turn->hours_end)) * 1 + (date("i", strtotime($turn->hours_end))) / 15) * 4;
        
    }

}
