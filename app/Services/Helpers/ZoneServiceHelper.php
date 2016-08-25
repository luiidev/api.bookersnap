<?php

namespace App\Services\Helpers;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ZoneServiceHelper
 *
 * @author USER
 */
use App\res_table;

class ZoneServiceHelper {
    
    public static function gatTableEntity(array $data, $zone) {        
        $entity = new res_table($data);
        $entity->res_zone_id = $zone->id;
        $entity->user_upd = $zone->user_upd;
        $entity->date_upd = $zone->date_upd;
        return $entity;
    }
}
