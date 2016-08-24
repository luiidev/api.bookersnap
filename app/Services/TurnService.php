<?php

namespace App\Services;

use App\res_table;
use App\res_turn;
use App\res_turn_zone_table;
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

    public function getList(int $microsite_id, array $params) {

        $rows = res_turn::where('ms_microsite_id', $microsite_id);
        if (isset($params['with'])) {
            $data = explode('|', $params['with']);
            $rows = (in_array("type_turn", $data)) ? $rows->with('type_turn') : $rows;
            $rows = (in_array("availability", $data)) ? $rows->with('availability') : $rows;
            $rows = (in_array("availability.zone", $data)) ? $rows->with('availability.zone') : $rows;
            $rows = (in_array("availability.rule", $data)) ? $rows->with('availability.rule') : $rows;
            $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
        }
        return $rows->get();
    }

    public function get(int $microsite_id, int $turn_id, $params) {
        try {
            $rows = res_turn::where('id', $turn_id)->where('ms_microsite_id', $microsite_id);
            if (isset($params['with'])) {
                $data = explode('|', $params['with']);
                $rows = (in_array("type_turn", $data)) ? $rows->with('type_turn') : $rows;
                $rows = (in_array("availability", $data)) ? $rows->with('availability') : $rows;
                $rows = (in_array("availability.zone", $data)) ? $rows->with('availability.zone') : $rows;
                $rows = (in_array("availability.rule", $data)) ? $rows->with('availability.rule') : $rows;
                $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
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

    public function create(array $data, int $microsite_id) {
        try {
            $turn = new res_turn();
            $turn->name = $data["name"];
            $turn->ms_microsite_id = $microsite_id;
            $turn->res_type_turn_id = $data["type_turn"]["id"];
            $turn->hours_ini = $data["hours_ini"];
            $turn->hours_end = $data["hours_end"];
            $turn->user_add = 1;
            $turn->date_add = \Carbon\Carbon::now();
            //$turn->res_zone_id = $zone;

            DB::BeginTransaction();
            $turn->save();

            /* foreach ($data['days'] as $key => $value) {
              $day_turn = new res_day_turn_zone();
              $day_turn->day = $value['day'];
              $day_turn->res_turn_id = $turn->id;
              $day_turn->res_type_turn_id	 = $data["type_turn"]["id"];

              $day_turn->save();
              } */

            DB::Commit();

            return $turn;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function update(array $data, int $id_turn) {
        $response = false;
        $dataUpdate = array();

        try {

            $now = \Carbon\Carbon::now();

            $turn = new res_turn();

            $dataUpdate["name"] = $data["name"];
            $dataUpdate["res_type_turn_id"] = $data["type_turn"]["id"];
            $dataUpdate["hours_ini"] = $data["hours_ini"];
            $dataUpdate["hours_end"] = $data["hours_end"];
            $dataUpdate["date_upd"] = $now;

            DB::BeginTransaction();

            $turn->where('id', $id_turn)->update($dataUpdate);

            DB::Commit();

            $response = true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        return $response;
    }

    /* public function validateTimeByTypeTurn(array $params, int $microsite_id){
      try{
      $turn = new res_turn();

      $rows = res_turn::where('id', $id)->where('ms_microsite_id', $microsite_id)->with('typeTurn')->get();

      return $turn;
      }  catch (\Exception $e){
      abort(500, $e->getMessage());
      }

      } */

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

}
