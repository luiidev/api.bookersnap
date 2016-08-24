<?php

namespace App\Services;

use App\res_turn_zone;

class TurnZoneService {

    public function save(int $turn_id, int $zone_id, int $rule_id) {
        $entity = res_turn_zone::where('res_turn_id', $turn_id)->where('res_zone_id', $zone_id)->first();
        if ($entity == null) {
            $entity = new res_turn_zone();
            $entity->res_turn_id = $turn_id;
            $entity->res_zone_id = $zone_id;
        }
        $entity->res_turn_rule_id = $rule_id;
        $entity->save();
    }

//    public function create(int $turn_id, int $zone_id, int $rule_id) {
//        try {
//            $entity = new res_turn_zone();
//            $entity->res_turn_id = $turn_id;
//            $entity->res_zone_id = $zone_id;
//            $entity->res_turn_rule_id = $rule_id;
//            $entity->save();
//        } catch (\Exception $e) {
//            //dd($e->getMessage());
//            abort(500, $e->getMessage());
//        }
//        return $entity;
//    }
//
//    public function update(int $turn_id, int $zone_id, int $rule_id) {
//        try {
//            $entity = res_turn_zone::where('res_turn_id', $turn_id)->where('res_zone_id', $zone_id)->first();
//            $entity->res_turn_rule_id = empty($rule_id) ? $entity->res_turn_rule_id : $rule_id;
//            $entity->save();
//        } catch (\Exception $e) {
//            abort(500, $e->getMessage());
//        }
//    }

}
