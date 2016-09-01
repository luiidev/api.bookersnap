<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZoneService;
use App\Http\Requests\ZoneRequest;
use App\Http\Controllers\Controller as Controller;

class ZoneController extends Controller {

    protected $_ZoneService;

    public function __construct(ZoneService $ZoneService) {
        $this->_ZoneService = $ZoneService;
    }

    public function index(Request $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $data = $service->getList($request->route('microsite_id'), $request->input('with'));
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

    public function show(Request $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->get($request->route('microsite_id'), $request->route('zone_id'), $request->input('with'));
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function create(ZoneRequest $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->create($request->all(), $request->route('microsite_id'), $request->input('_bs_user_id'));
                    return response()->json($result);
                });
    }

    public function update(ZoneRequest $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->update($request->all(), $request->route('zone_id'), $request->input('_bs_user_id'));
                    return response()->json($result);
                });
    }

    public function delete(Request $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->delete($request->route('microsite_id'), $request->route('zone_id'));
                    return response()->json($result);
                });
    }

    public function listTable(Request $request) {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
                    $result = $service->getListTable($request->route('microsite_id'), $request->route('zone_id'));
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    
}
