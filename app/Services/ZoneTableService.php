<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

use App\res_table;

class ZoneTableService
{

    /**
     * Verificar si existe Id de una mesa.
     * @param   int     $zone_id  Identificador la zona.
     * @param   int     $table_id  Identificador la mesa.
     * @return  boolean [true: si existe la mesa] [false: mesa no existe]
     */
    public function exists(int $zone_id, int $table_id = null)
    {
        return (res_table::where('id', $table_id)->where('res_zone_id', $zone_id)->get()->count() > 0) ? true : false;
    }

    /**
     * Obtener una zona de un micrositio.
     * @param   array           $data  Estructura de datos de la mesa a registrar.
     * @param   int             $zone_id  Identificador del la zona a la que pertenecera la mesa.
     * @param   int             $user_id  Identificador del usuario que va ha registrar la mesa.
     * @return  App\res_table   Objeto mesa de reservacion.
     */
    public function create(array $data, int $zone_id, int $user_id)
    {
        try {
            $date_now                     = \Carbon\Carbon::now();
            $entity                       = new res_table();
            $entity->res_zone_id          = $zone_id;
            $entity->name                 = $data['name'];
            $entity->min_cover            = $data['min_cover'];
            $entity->max_cover            = $data['max_cover'];
            $entity->price                = isset($data['price']) ? $data['price'] : 0.0;
            $entity->config_color         = isset($data['config_color']) ? $data['config_color'] : '#fff';
            $entity->config_position      = isset($data['config_position']) ? $data['config_position'] : rand(100, 500) . "," . rand(100, 500);
            $entity->config_forme         = isset($data['config_forme']) ? $data['config_forme'] : 1;
            $entity->config_size          = isset($data['config_size']) ? $data['config_size'] : 2;
            $entity->config_rotation      = isset($data['config_rotation']) ? $data['config_rotation'] : 0;
            $entity->config_rotation_text = isset($data['config_rotation_text']) ? $data['config_rotation_text'] : 0;
            $entity->user_upd             = $user_id;
            $entity->user_add             = $user_id;
            $entity->date_add             = $date_now;
            $entity->date_upd             = $date_now;
            $entity->save();
            return $entity;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
        return null;
    }

    /**
     * Obtener una zona de un micrositio.
     * @param   array           $data       Estructura de datos de la mesa a editar.
     * @param   int             $table_id   Identificador del la mesa a editar.
     * @param   int             $user_id    Identificador del usuario que va ha editar la mesa.
     * @return  boolean         [true: registro exitoso] [false: no se actualizaron los datos]
     */
    public function update(array $data, int $table_id, int $user_id)
    {

        try {
            $date_now                     = \Carbon\Carbon::now();
            $entity                       = res_table::where('id', $table_id)->first();
            $entity->name                 = isset($data['name']) ? $data['name'] : $entity->name;
            $entity->min_cover            = isset($data['min_cover']) ? $data['min_cover'] : $entity->min_cover;
            $entity->max_cover            = isset($data['max_cover']) ? $data['max_cover'] : $entity->max_cover;
            $entity->price                = isset($data['price']) ? $data['price'] : $entity->price;
            $entity->config_color         = isset($data['config_color']) ? $data['config_color'] : $entity->config_color;
            $entity->config_position      = isset($data['config_position']) ? $data['config_position'] : $entity->config_position;
            $entity->config_forme         = isset($data['config_forme']) ? $data['config_forme'] : $entity->config_forme;
            $entity->config_size          = isset($data['config_size']) ? $data['config_size'] : $entity->config_size;
            $entity->config_rotation      = isset($data['config_rotation']) ? $data['config_rotation'] : $entity->config_rotation;
            $entity->config_rotation_text = isset($data['config_rotation_text']) ? $data['config_rotation_text'] : $entity->config_rotation_text;
            $entity->date_upd             = $date_now;
            $entity->user_upd             = $user_id;
            $entity->status               = $data['status'];
            $entity->save();
            return true;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
        return false;
    }

}
