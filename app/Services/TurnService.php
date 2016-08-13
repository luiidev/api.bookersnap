<?php

namespace App\Services;

use App\res_turn;
use App\res_day_turn_zone;
use Illuminate\Support\Facades\DB;

class TurnService {

	public function getList(int $microsite_id, int $zone){

		$rows = res_turn::where('ms_microsite_id', $microsite_id)->where('res_zone_id', $zone)->with('typeTurn')->with('days')->get();
  
        return $rows->toArray();
	}

	public function get(int $microsite_id, int $id) {
		try{
			$rows = res_turn::where('id', $id)->where('ms_microsite_id', $microsite_id)->with('type')->first();

			if($rows == null){
				abort(500, "Ocurrio un error");
			}

			return $rows->toArray();
				
		} catch (\Exception $e){
			abort(500, $e->getMessage());
		}
        
    }

	public function create(array $data, int $microsite_id,int $zone){
		try{
            $turn = new res_turn();
            $turn->name = $data["name"];
            $turn->ms_microsite_id = $microsite_id;
            $turn->res_type_turn_id = $data["type_turn"]["id"];
            $turn->hours_ini = $data["hours_ini"];
            $turn->hours_end = $data["hours_end"];
            $turn->user_add = 1;
            $turn->date_add = \Carbon\Carbon::now();
            $turn->res_zone_id = $zone;

            DB::BeginTransaction();
            $turn->save();

            foreach ($data['days'] as $key => $value) {
            	$day_turn = new res_day_turn_zone();
            	$day_turn->day = $value['day'];
            	$day_turn->res_turn_id = $turn->id;
            	$day_turn->res_type_turn_id	 = $data["type_turn"]["id"];

            	$day_turn->save();
			}

            DB::Commit();
        }  catch (\Exception $e){
            DB::rollBack();
            abort(500, $e->getMessage());
        }
	}

	public function update(array $data,int $id_turn){
        $response = false;
        $dataUpdate = array();

        try{

            $now = \Carbon\Carbon::now();

            $turn = new res_turn();

            $dataUpdate["name"] = $data["name"];
            $dataUpdate["res_type_turn_id"] = $data["type"]["id"];
			$dataUpdate["hours_ini"] = $data["hours_ini"];
			$dataUpdate["hours_end"] = $data["hours_end"];
			$dataUpdate["date_upd"] = $now;

            DB::BeginTransaction();

            $turn->where('id',$id_turn)->update($dataUpdate);

            DB::Commit();

            $response = true;
           
        }  catch (\Exception $e){
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        return $response;
    }

}