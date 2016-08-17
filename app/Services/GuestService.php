<?php

namespace App\Services;

use App\res_guest;
use Illuminate\Support\Facades\DB;

class GuestService {

	public function getList(int $microsite_id,array $params){
		$rows = res_guest::where('ms_microsite_id',$microsite_id)->with('email')->with('phone');

		$name = !isset($params['name'])? '' : $params['name'];
		$page_size = (!empty($params['page_size']) && $params['page_size'] <= 100) ? $params['page_size'] : 30;

		$rows = $rows->where('first_name', 'LIKE', '%'.$name.'%')->paginate($page_size);

        return $rows;
	}

	public function get(int $microsite_id, int $id){
		try{
			$rows = res_guest::where('id', $id)->where('ms_microsite_id', $microsite_id)->with('email')->with('phone')->first();

			if($rows == null){
				abort(500, "Ocurrio un error");
			}

			return $rows->toArray();
				
		} catch (\Exception $e){
			abort(500, $e->getMessage());
		} 
	}
}