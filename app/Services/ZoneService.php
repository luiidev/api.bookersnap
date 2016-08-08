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
use App\res_zone;
use App\res_table;
use Illuminate\Support\Facades\DB;

class ZoneService {
    
    /**
     * lista de todas las zonas de un micrositio.
     * @param   int     $microsite_id  Identificador del micrositio.
     * @return  array   Lista de Estructura de zonas
     */    
    public function getList(int $microsite_id) {
        $rows = res_zone::where('ms_microsite_id', $microsite_id)->where('status', '<>', '2')->with('tables')->with('turns.days')->with('turns.type')->get();
        return $rows->toArray();
    }
    
    public function get(int $microsite_id, int $id) {
        $rows = res_zone::where('id', $id)->where('ms_microsite_id', $microsite_id)->where('status', '<>', '2')->with('tables')->first();
        return $rows->toArray();
    }
    
    public function create(array $data, int $microsite_id) {
        try{
            $zone = new res_zone();
            $zone->name = $data['name'];
            $zone->sketch = $data['sketch'];
            $zone->status = $data['status'];
            $zone->type_zone = $data['type_zone'];
            $zone->join_table = $data['join_table'];
            $zone->status_smoker = $data['status_smoker'];
            $zone->people_standing = $data['people_standing'];
            $zone->ms_microsite_id = $microsite_id;

            DB::BeginTransaction();
            $zone->save();
            foreach ($data['tables'] as $key => $value) {
                $zone = $this->insertTables($zone, $value['day']);
            }
            DB::Commit();
        }  catch (\Exception $e){
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
    }
    
    private function insertTables(res_zone $zone, array $data) {
        try {
            $table = new res_table();
            $table->ms_microsite_id = $zone->ms_microsite_id;
            $table->res_zone_id = $zone->id;
            $table->name = $data['name'];
            $table->min_cover = $data['min_cover'];
            $table->max_cover = $data['max_cover'];
            $table->price = $data['price'];
            $table->status = $data['status'];
            $table->config_color = $data['config_color'];
            $table->config_position = $data['config_position'];
            $table->config_forme = $data['config_forme'];
            $table->config_size = $data['config_size'];
            $table->config_rotation = $data['config_rotation'];
            $zone->tables()->save($table);
        } catch (\Exception $e) {
            
        }
        return $turn;
    }
    
    
    /**
     * Obtener turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param id int        Identificador del turno de una zona.
     * @return array        Estructura turno 
     */
    public function availableDaysForTypeturn(int $zone_id, int $type_turn_id) {
        try {            
            $turnos = res_turn_zone::where('res_type_turn_zone_id', $type_turn_id)->where('res_zone_id', $zone_id)->get()->map(function($item, $key){
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
