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
use App\Services\ZoneTableService;

class ZoneService {

    protected $_ZoneTableService;
 
    public function __construct(ZoneTableService $ZoneTableService) {
        $this->_ZoneTableService = $ZoneTableService;
    }
    
    /**
     * lista de todas las zonas de un micrositio.
     * @param   int     $microsite_id  Identificador del micrositio.
     * @return  array   Lista de Estructura de zonas
     */    
    public function getList(int $microsite_id) {

        $rows = res_zone::where('ms_microsite_id', $microsite_id)->with('tables')->with('zoneTurns.turns')->get()->map(function($item){
            return $item;
        });
  
        return $rows->toArray();
    }
    
    public function get(int $microsite_id, int $id) {
        $rows = res_zone::where('id', $id)->where('ms_microsite_id', $microsite_id)->with('tables')->first();
        return $rows->toArray();
    }
    
    public function create(array $data, int $microsite_id) {
        try{
            $zone = new res_zone();
            $zone->name = $data['name'];
            //$zone->sketch = empty($data['sketch']) ? null : $data['sketch'] ;
            //$zone->status = empty($data['status']) ? 0: $data['status'];
            //$zone->type_zone = empty($data['type_zone']) ? 0 : $data['type_zone'];
            //$zone->join_table = empty($data['join_table']) ? 0 : $data['join_table'];
            //$zone->status_smoker = empty($data['status_smoker']) ? 0 : $data['status_smoker'];
            //$zone->people_standing = empty($data['people_standing']) ? 0 : $data['people_standing'];
            $zone->ms_microsite_id = $microsite_id;
            $zone->user_add = 1;
            $zone->date_add = \Carbon\Carbon::now();
            DB::BeginTransaction();
            $zone->save();

            foreach ($data['tables'] as $key => $value) {

                $zone = $this->_ZoneTableService->create($zone, $value);
            }
            DB::Commit();
        }  catch (\Exception $e){
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
    }
        
    public function update(array $data,int $id_zone){
        $response = false;

        try{

            $now = \Carbon\Carbon::now();

            $zone = new res_zone();

            $zone->user_add = 1;
            $zone->date_add = $now;
            $zone->id = $id_zone;

            DB::BeginTransaction();

            $zone->where('id',$id_zone)->update(["name" => $data["name"],"date_upd" => $now ]);

            foreach ($data['tables'] as $key => $value) {

                $exists = $this->_ZoneTableService->exists($value["id"]);
        
                if($exists){
                    
                    $this->_ZoneTableService->update($value);
                }else{

                    $this->_ZoneTableService->create($zone, $value);
                }
 
            }

            DB::Commit();

            $response = true;
           
        }  catch (\Exception $e){
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        return $response;
    }

    public function delete(int $id_zone){
        $response = false;

        try{

            $zone = new res_zone();

            DB::BeginTransaction();

            $zone->where('id',$id_zone)->update(["status" => 2]);

            DB::Commit();

            $response = true;

        }catch (\Exception $e){
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        return $response;
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
