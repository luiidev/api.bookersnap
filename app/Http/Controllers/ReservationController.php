<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Requests\ReservationRequest;
use App\Http\Controllers\Controller as Controller;
use App\Services\ReservationService;
use Carbon\Carbon;

class ReservationController extends Controller
{
	protected $_ReservationService;
	public function __construct(ReservationService $ReservationService){
		$this->_ReservationService=$ReservationService;
	}
    public function index(Request $request) {
    	$service = $this->_ReservationService;
    	    	
        return $this->TryCatch(function () use ($request, $service) {
        			
					$date = Carbon::now();
					$date = $date->format('Y-m-d');

                    $data = $service->getList($request->route('microsite_id'), $date);
        			//$data=['name'=>'jesus'];
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

    public function create(ReservationRequest $request) {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->create($request->all(), $request->route('microsite_id'), $request->_bs_user_id);
                    return response()->json($result);
                });
                
    }

}
