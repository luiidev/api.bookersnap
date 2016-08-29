<?php

namespace App\Services;

use App\res_table;
use App\res_turn;
use App\res_turn_zone;
use App\Services\TurnZoneService;
use Illuminate\Support\Facades\DB;

class TurnService {

    public function search(int $microsite_id, array $params) {
        $rows = res_turn::with('typeTurn')->where('ms_microsite_id', $microsite_id);
        if (!empty($params)) {
            if (!empty($params['hours_ini'])) {
                $rows = $rows->where('hours_ini', $params['hours_ini']);
            }

            if (!empty($params['hours_end'])) {
                $rows = $rows->where('hours_end', $params['hours_end']);
            }
            if (!empty($params['type_turn'])) {
                $rows = $rows->where('res_type_turn_id', $params['type_turn']);
            }
        }
        $rows = $rows->get();

        return $rows;
    }

    public function getList(int $microsite_id, string $with = null) {

        $rows = res_turn::where('ms_microsite_id', $microsite_id);
        if (isset($with)) {
            $data = explode('|', $with);
            $rows = (in_array("type_turn", $data)) ? $rows->with('typeTurn') : $rows;
            $rows = (in_array("turn_zone", $data)) ? $rows->with('turnZone') : $rows;
            $rows = (in_array("turn_zone.zone", $data)) ? $rows->with('turnZone.zone') : $rows;
            $rows = (in_array("turn_zone.zone.turns", $data)) ? $rows->with('turnZone.zone.turns') : $rows;
            $rows = (in_array("turn_zone.zone.tables", $data)) ? $rows->with('turnZone.zone.tables') : $rows;
            $rows = (in_array("turn_zone.rule", $data)) ? $rows->with('turnZone.rule') : $rows;
            $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
            $rows = (in_array("zones.tables", $data)) ? $rows->with('zones.tables') : $rows;
            $rows = (in_array("zones.turns", $data)) ? $rows->with('zones.turns') : $rows;
        }
        return $rows->get();
    }

    public function get(int $microsite_id, int $turn_id, $with) {
        try {
            $rows = res_turn::where('id', $turn_id)->where('ms_microsite_id', $microsite_id);
            if (isset($with)) {
                $data = explode('|', $with);
                $rows = (in_array("type_turn", $data)) ? $rows->with('typeTurn') : $rows;
                $rows = (in_array("turn_zone", $data)) ? $rows->with('turnZone') : $rows;
                $rows = (in_array("turn_zone.zone", $data)) ? $rows->with('turnZone.zone') : $rows;
                $rows = (in_array("turn_zone.zone.turns", $data)) ? $rows->with('turnZone.zone.turns') : $rows;
                $rows = (in_array("turn_zone.zone.tables", $data)) ? $rows->with('turnZone.zone.tables') : $rows;
                $rows = (in_array("turn_zone.rule", $data)) ? $rows->with('turnZone.rule') : $rows;
                $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
                $rows = (in_array("zones.tables", $data)) ? $rows->with('zones.tables') : $rows;
                $rows = (in_array("zones.turns", $data)) ? $rows->with('zones.turns') : $rows;
            }
            $rows = $rows->first();
            if ($rows == null) {
                abort(500, "Ocurrio un error");
            }
            return $rows;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function create(array $data, int $microsite_id, int $user_id) {
        try {
            $turn = new res_turn();
            $turn->name = $data["name"];
            $turn->ms_microsite_id = $microsite_id;
            $turn->res_type_turn_id = $data["res_type_turn_id"];
            $turn->hours_ini = $data["hours_ini"];
            $turn->hours_end = $data["hours_end"];
            $turn->user_add = $user_id;
            $turn->user_upd = $user_id;
            $turn->date_add = \Carbon\Carbon::now();
            $turn->date_upd = $turn->date_add;

            DB::BeginTransaction();
            $turn->save();
            foreach ($data['turn_zone'] as $value) {
                $turn->zones()->attach($value['res_zone_id'], ['res_turn_rule_id' => $value['res_turn_rule_id']]);
//                if(is_array($value["tables"])){
//                    foreach ($value["tables"] as $table) {
//                        $rows = $this->createTurnTable($table["availability"], $turn->hours_ini, $turn->hours_end, $turn->id, $zone_id, $table_id);
//                    }
//                }
            }
            DB::Commit();
            $res_turn = res_turn_zone::where('res_turn_id', $turn->id)->get();
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }
    
//    public function availabilityTables(array $data, int $turn_id, int $zone_id) {
//        
//    }

    public function update(array $data, int $microsite_id, int $turn_id, int $user_id) {

        try {
            $turn = res_turn::where('id', $turn_id)->where('ms_microsite_id', $microsite_id)->first();
            $turn->name = $data["name"];
            $turn->res_type_turn_id = $data["res_type_turn_id"];
            $turn->hours_ini = $data["hours_ini"];
            $turn->hours_end = $data["hours_end"];
            $turn->user_upd = $user_id;
            $turn->date_upd = \Carbon\Carbon::now();

            DB::BeginTransaction();
            $turn->save();
            foreach ($data['turn_zone'] as $value) {
                if (DB::table('res_turn_zone')->where('res_turn_id', $turn_id)->where('res_zone_id', $value['res_zone_id'])->count() > 0) {
                    $turn->zones()->updateExistingPivot($value['res_zone_id'], ['res_turn_rule_id' => $value['res_turn_rule_id']]);
                } else {
                    $turn->zones()->attach($value['res_zone_id'], ['res_turn_rule_id' => $value['res_turn_rule_id']]);
                }
            }
            DB::Commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
        return false;
    }

    public function unlinkZone(int $microsite_id, int $turn_id, int $zone_id) {
        try {
            if (res_turn::where('ms_microsite_id', $microsite_id)->where('id', $turn_id)->get()->count() > 0) {
                DB::BeginTransaction();
                DB::table('res_turn_zone_table')->where('res_turn_id', $turn_id)->where('res_zone_id', $zone_id)->delete();
                $turn = res_turn::findOrFail($turn_id);
                $turn->zones()->detach($zone_id);
                DB::Commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function getListTable(int $turn_id, int $zone_id) {
        
        $turn = res_turn::where('id', $turn_id)->first();
        if ($turn != null) {
            $EnableTimesForTable = new \App\Domain\EnableTimesForTable();

            $tables = res_table::where('res_zone_id', $zone_id)->where('status', 1)->with(array('turns' => function($query) use($turn_id, $zone_id) {
                            $query->where('res_turn_id', $turn_id)->where('res_zone_id', $zone_id);
                        }))->get(array('id', 'name', 'min_cover', 'max_cover'))->map(function($item) use($turn, $EnableTimesForTable) {
                $item->availability = $EnableTimesForTable->segment($turn, $item->turns);
                unset($item->turns);
                return $item;
            });
            return $tables;
        }
        return $turn;
    }

    public function createTurnTable(array $time_range, int $start_time, int $end_time, int $turn_id, int $zone_id, int $table_id) {

        $time_ini = \App\Domain\TimeForTable::timeToIndex($start_time);
        $time_end = \App\Domain\TimeForTable::timeToIndex($end_time);

        $rowsTurnTable = [];
        $new_rule = -1;
        $turnTable = [
            "res_turn_id" => $turn_id,
            "res_zone_id" => $zone_id,
            "res_table_id" => $table_id,
        ];
        for ($i = $time_ini; $i < $time_end; $i++) {
            $rule = $time_range[$i];
            $change = false;
            if ((@$time_range[$i - 1] && $time_range[$i - 1] != $time_range[$i]) || $i == $time_ini) {
                $turnTable['start_time'] = \App\Domain\TimeForTable::indexToTime($i);
                $turnTable['end_time'] = $turnTable['start_time'];
                $turnTable['res_turn_rule_id'] = ($time_range[$i] != -1) ? $time_range[$i] : 0;
            }
            if ((@$time_range[$i - 1] && $time_range[$i - 1] == $time_range[$i])) {
                $turnTable['end_time'] = \App\Domain\TimeForTable::indexToTime($i);
            }

            if ((@$time_range[$i + 1] && $time_range[$i + 1] != $time_range[$i]) || $i == $time_end - 1) {
                $rowsTurnTable[] = $turnTable;
            }
        }
        return $rowsTurnTable;
    }

}
