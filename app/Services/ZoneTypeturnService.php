<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

/**
 * Description of ZoneTypeTurnDayService
 *
 * @author USER
 */
use App\res_turn_zone;
use App\res_day_turn_zone;
use App\res_type_turn_zone;
use App\Domain\TurnDomain;
use App\res_zone;
class ZoneTypeturnService {

    /**
     * Obtener turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param id int        Identificador del turno de una zona.
     * @return array        Estructura turno 
     */
    public function getList(int $zone_id, int $type_turn_id) {
        try {
//            $rows = res_zone::with('tables')->with('turns.days')->with('turns.type')->get();
//        return $rows->toArray();
            
            $typeturn = res_type_turn_zone::with('turns.zone')->with('turns.days')->get();
            return $typeturn->toArray();
            
//            $typeturn = res_type_turn_zone::all()->map(function($item, $key) {
//                
//                $turnos = res_type_turn_zone::all()->with('turns')->get()->toArray();
////                $dias = res_day_turn_zone::whereIn('res_turn_zone_id', array($key))
////                        ->distinct()
////                        ->get();
////                $item->days = $dias->toArray();
//                return $item;
//            });
//            return $typeturn->toArray();

//            $turnos = res_turn_zone::where('res_type_turn_zone_id', $type_turn_id)->where('res_zone_id', $zone_id)->get()->map(function($item, $key) {
//                        return $item->id;
//                    })->toArray();
//            $dias = res_day_turn_zone::whereIn('res_turn_zone_id', $turnos)
//                    ->distinct()
//                    ->get();
//
//            return $dias->toArray();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getFile());
            var_dump($e->getLine());
//            var_dump($e->getTrace());
            exit;
            return array();
        }
    }

    /**
     * Obtener turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param id int        Identificador del turno de una zona.
     * @return array        Estructura turno 
     */
    public function getListAvailable(int $zone_id, int $type_turn_id) {
        try {
            $turnos = res_turn_zone::where('res_type_turn_zone_id', $type_turn_id)->where('res_zone_id', $zone_id)->get()->map(function($item, $key) {
                        return $item->id;
                    })->toArray();

            $dias = res_day_turn_zone::whereIn('res_turn_zone_id', $turnos)
                    ->distinct()
                    ->get();

            $turnDomain = new TurnDomain();
            return $turnDomain->availableDays($dias);
        } catch (\Exception $e) {
            return array();
        }
    }

}
