<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\res_zone;
use App\res_table;
use App\Services\ZoneService;
use App\Http\Requests\ZoneRequest;
use App\Http\Controllers\Controller as Controller;

class ZoneController extends Controller {

    protected $_ZoneService;
 
    public function __construct(ZoneService $ZoneService) {
        $this->_ZoneService = $ZoneService;
    }

    public function index($lang, int $microsite_id) {

        return $this->TryCatch(function () use ($microsite_id) {
            $data = $this->_ZoneService->getList($microsite_id);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }


    public function show($lang, int $microsite_id, int $id) {

        return $this->TryCatch(function () use ($id,$microsite_id) {
            $result = $this->_ZoneService->get($microsite_id, $id);
            return $this->CreateResponse(true, 201, "", $result);
        });  
    }

    public function create(ZoneRequest $request, $lang, int $microsite_id) {
        return $this->TryCatch(function () use ($request,$microsite_id) {
            $result = $this->_ZoneService->create($request->all(), $microsite_id);
            return response()->json($result);
        });
    }

    public function update(ZoneRequest $request, $lang, int $microsite_id, int $id) {
  
        return $this->TryCatch(function () use ($request,$microsite_id,$id) {
            $result = $this->_ZoneService->update($request->all(), $id);
            return response()->json($result);
        });
    }

    public function delete($lang, int $microsite_id, int $id) {

        return $this->TryCatch(function () use ($id) {
            $result = $this->_ZoneService->delete($id);
            return response()->json($result);
        });
    }

}
