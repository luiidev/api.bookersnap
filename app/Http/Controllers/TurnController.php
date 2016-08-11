<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use App\Services\TurnService;

class TurnController extends Controller
{
    protected $_TurnService;
 
    public function __construct(TurnService $TurnService) {
        $this->_TurnService = $TurnService;
    }

    public function index($lang, int $microsite_id)
    {
        return $this->TryCatch(function () use ($microsite_id) {
            $data = $this->_TurnService->getList($microsite_id);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function show($lang, int $microsite_id, int $id) {

        return $this->TryCatch(function () use ($id,$microsite_id) {
            $result = $this->_TurnService->get($microsite_id, $id);
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

    public function delete($id)
    {
        //
    }
}
