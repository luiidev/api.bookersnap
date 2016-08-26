<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\TurnRequest;
use Illuminate\Support\Facades\Input;
use App\Services\TurnService;

class TurnController extends Controller {

    protected $_TurnService;

    public function __construct(TurnService $TurnService) {
        $this->_TurnService = $TurnService;
    }

    public function index(Request $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $data = $service->getList($request->route('microsite_id'), $request->input('with'));
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

    public function show(Request $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->get($request->route('microsite_id'), $request->route('turn_id'), $request->input('with'));
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function create(TurnRequest $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->create($request->all(), $request->route('microsite_id'), $request->_bs_user_id);
                    return response()->json($result);
                });
    }

    public function update(TurnRequest $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->update($request->all(), $request->route('microsite_id'), $request->route('turn_id'), $request->_bs_user_id);
                    return response()->json($result);
                });
    }

    public function search(Request $request) {/* evaluando su eliminacion */
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->search($request->route('microsite_id'), $request->input());
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function unlinkZone(Request $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $service->unlinkZone($request->route('microsite_id'), $request->route('turn_id'), $request->route('zone_id'));
                    return $this->CreateResponse(true, 200);
                });
    }

    public function listTableZone(Request $request) {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->getListTable($request->route('turn_id'), $request->route('zone_id'));
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }
    
}
