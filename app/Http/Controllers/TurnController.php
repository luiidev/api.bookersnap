<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use App\Services\TurnService;

class TurnController extends Controller
{
    protected $_TurnService;
 
    public function __construct(TurnService $TurnService) {
        $this->_TurnService = $TurnService;
    }

    public function index(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $params = $request->input();
        return $this->TryCatch(function () use ($microsite_id, $params) {
            $data = $this->_TurnService->getList($microsite_id, $params);
            return $this->CreateResponse(true, 201, "", $data);
        });

    }

    public function show($lang, int $microsite_id,$id) {

        return $this->TryCatch(function () use ($microsite_id,$id) {
            $result = $this->_TurnService->get($microsite_id,$id);
            return $this->CreateResponse(true, 201, "", $result);
        });  
    }
  
    public function create(Request $request, $lang, int $microsite_id)
    {
        return $this->TryCatch(function () use ($request,$microsite_id) {
            $result = $this->_TurnService->create($request->all(), $microsite_id);
            return response()->json($result);
        });
    }

    public function update(Request $request, $lang, int $microsite_id, int $id)
    {
        return $this->TryCatch(function () use ($request,$microsite_id,$id) {
            $result = $this->_TurnService->update($request->all(), $id);
            return response()->json($result);
        });
    }

    public function search($lang, int $microsite_id) {

        $params = Input::get();

        return $this->TryCatch(function () use ($microsite_id,$params) {
            $result = $this->_TurnService->search($microsite_id,$params);
            return $this->CreateResponse(true, 201, "", $result);
        }); 

    }


    public function delete($id)
    {
        //
    }
    
    public function tableAvailability(Request $request)
    {
        $turn_id = $request->route('turn_id');
        $zone_id = $request->route('zone_id');
        $data = $request->all();
        return $this->TryCatch(function () use ($turn_id, $zone_id){
            $result = $this->_TurnService->tableAvailability($turn_id, $zone_id);
            return $this->CreateResponse(true, 201, "", $result);
        });
    }
}
