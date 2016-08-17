<?php

namespace App\Services;

use App\res_guest;
use Illuminate\Support\Facades\DB;

class GuestService {

	public function getList(){
		$rows = res_guest::with('email')->with('phone')->get();

        return $rows->toArray();
	}
}