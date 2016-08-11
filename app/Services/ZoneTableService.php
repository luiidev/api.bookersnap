<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use App\res_table;
use Illuminate\Support\Facades\DB;

class ZoneTableService {

	public function exists($id){
	    $response = FALSE;
	    $row = res_table::where('id', $id)->get()->count();

		if($row > 0){
			$response = TRUE;
		}

		return $response;
	}

	public function create($zone, array $data) {
        try {
          
            $table = new res_table();
            //$table->ms_microsite_id = $zone->ms_microsite_id;
            $table->res_zone_id = $zone->id;
            $table->name = $data['name'];
            $table->min_cover = $data['min_cover'];
            $table->max_cover = $data['max_cover'];
            //$table->price = $data['price'];
            //$table->status = $data['status'];
           // $table->config_color = $data['config_color'];
            $table->config_position = $data['config_position'];
            $table->config_forme = $data['config_forme'];
            $table->config_size = $data['config_size'];
            $table->config_rotation = $data['config_rotation'];
            $table->date_add = $zone->date_add;
            $table->user_add = $zone->user_add;
            
            $zone->tables()->save($table);
        } catch (\Exception $e) {
            //dd($e->getMessage());
            abort(500, $e->getMessage());
        }
        return $zone;
    }

	public function update(array $data){

		try {
          
            $table = new res_table();

           	$table->where('id',$data["id"])->update([
           		"config_forme" => $data["config_forme"],
           		"config_size" => $data["config_size"],
           		"config_rotation" => $data["config_rotation"],
           		"config_position" => $data["config_position"],
           		"name" => $data["name"],
           		"min_cover" => $data["min_cover"],
           		"max_cover" => $data["max_cover"],
           		"status" => $data["status"]

           	]);
            
        } catch (\Exception $e) {

            abort(500, $e->getMessage());
        }

	}
}