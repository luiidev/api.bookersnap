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

    public function exists($id) {
        $response = FALSE;
        $row = res_table::where('id', $id)->get()->count();

        if ($row > 0) {
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

    public function update(array $data, int $id) {

        try {
            $table = res_table::where('id', $id)->first();
            $table->name = empty($data['name']) ? $entity->name : $data['name'];
            $table->min_cover = empty($data['min_cover']) ? $entity->min_cover : $data['min_cover'];
            $table->max_cover = empty($data['max_cover']) ? $entity->max_cover : $data['max_cover'];            
            $table->price = empty($data['price']) ? $entity->price : $data['price'];
            $table->status = empty($data['status']) ? $entity->status : $data['status'];
             $table->config_color = empty($data['config_color']) ? $entity->config_color : $data['config_color'];
            
            $table->config_position = empty($data['config_position']) ? $entity->config_position : $data['config_position'];
            $table->config_forme = empty($data['config_forme']) ? $entity->config_forme : $data['config_forme'];
            $table->config_size = empty($data['config_size']) ? $entity->config_size : $data['config_size'];
            $table->config_rotation = empty($data['config_rotation']) ? $entity->config_rotation : $data['config_rotation'];
            $table->date_upd = empty($data['date_upd']) ? $entity->date_upd : $data['date_upd'];
            $table->user_upd = empty($data['user_upd']) ? $entity->user_upd : $data['user_upd'];
        } catch (\Exception $e) {

            abort(500, $e->getMessage());
        }
    }

}
