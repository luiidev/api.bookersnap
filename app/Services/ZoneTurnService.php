<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

/**
 * Description of ZoneService
 *
 * @author USER
 */
use App\res_turn_zone;
use App\res_day_turn_zone;
use App\res_zone;
use App\res_table;
use Illuminate\Support\Facades\DB;

class ZoneTurnService {

    /**
     * lista de todos los turnos de una zona.
     * @param zone_id int   Identificador de la zona de un local.
     * @return array        Estructura de turnos
     */
    public function getList(int $zone_id) {
        $rows = res_turn_zone::where('res_zone_id', $zone_id)->where('status', '<>', '2')->with('days')->with('type')->get();
        return $rows->toArray();
    }

    /**
     * Obtener turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param id int        Identificador del turno de una zona.
     * @return array        Estructura turno 
     */
    public function get(int $zone_id, int $id) {
        $rows = res_turn_zone::where('id', $id)->where('res_zone_id', $zone_id)->where('status', '<>', '2')->with('days')->with('type')->first();
        return $rows->toArray();
    }

    /**
     * Crear turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param data array    Estructura de datos de nuevo turno.
     * @return array        Estructura turno. 
     */
    public function create(int $zone_id, array $data) {
        try {
            $turn = new res_turn_zone();
            $turn->hours_ini = $data['hours_ini'];
            $turn->hours_end = $data['hours_end'];
            $turn->status = $data['status'];
            $turn->on_table = $data['on_table'];
            $turn->early = $data['early'];
            $turn->ms_microsite_id = $data['microsite_id'];
            $turn->res_zone_id = $zone_id;
            $turn->res_type_turn_zone_id = $data['type_turn_id'];

            DB::BeginTransaction();
            $turn->save();
            foreach ($data['days'] as $key => $value) {
                $this->deleteDays($turn);
                $turn = $this->insertDays($turn, $value['day']);
            }
            DB::Commit();
            return $turn;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
//            abort(500, $e->getMessage(). " ". $e->getFile(). " ". $e->getLine());
        }
        return null;
    }

    /**
     * Actualizar turno con sus dias asignados.
     * @param id int        Identificador del turno de una zona.
     * @param zone_id int   Identificador de la zona de un local.
     * @param data array    Estructura de datos de nuevo turno.
     * @return array        Estructura turno. 
     */
    public function update(int $id, int $zone_id, array $data) {

        try {
            $turn = res_turn_zone::where('id', $id)->where('res_zone_id', $zone_id)->with('days')->with('type')->first();
            $turn->hours_ini = $data['hours_ini'];
            $turn->hours_end = $data['hours_end'];
            $turn->status = $data['status'];
            $turn->on_table = $data['on_table'];
            $turn->early = $data['early'];
//        $turn->ms_microsite_id = $data['microsite_id'];
//        $turn->res_zone_id = $zone_id;
            $turn->res_type_turn_zone_id = $data['type_turn_id'];

            DB::BeginTransaction();
            $turn->save();
            foreach ($data['days'] as $key => $value) {
                $this->deleteDays($turn);
                $turn = $this->insertDays($turn, $value['day']);
            }
            DB::Commit();
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
        return $turn;
    }

    /**
     * Eliminacion logica de turno y eliminacion fisica de los dias del turno.
     * @param id int        Identificador del turno de una zona.
     * @param zone_id int   Identificador de la zona de un local.
     * @return array        Estructura turno. 
     */
    public function delete(int $id, int $zone_id) {
        try {
            $turn = res_turn_zone::where('id', $id)->where('res_zone_id', $zone_id)->with('days')->with('type')->first();
            $turn->status = 2;

            DB::BeginTransaction();
            $turn->delete();
            $this->deleteDays($turn);
            DB::Commit();
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
        return $turn;
    }

    /**
     * Eliminacion fisica de los dias del objeto App\res_turn_zone.
     * @param turn App\res_turn_zone        Objeto turno de una zona.
     * @return array                    Estructura turno. 
     */
    private function deleteDays(res_turn_zone $turn) {
        return res_day_turn_zone::where('res_turn_zone_id', $turn->id)->delete();
    }

    private function insertDays(res_turn_zone $turn, $day) {
        try {
            $result = res_day_turn_zone::where('day', $day)->where('res_zone_id', $turn->res_zone_id)->first();
        } catch (\Exception $e) {
            $day = new res_day_turn_zone();
            $day->day = $day;
            $day->res_zone_id = $turn->res_zone_id;
            $day->ms_microsite_id = $turn->ms_microsite_id;
            $day->res_turn_zone_id = $turn->id;
            $turn->days()->save($day);
        }
        return $turn;
    }
    
}
