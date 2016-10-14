<?php

namespace App\Http\Controllers;

use App\Http\Requests\TurnRequest;
use App\Services\TurnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class TurnController extends Controller
{

    protected $_TurnService;

    public function __construct(TurnService $TurnService)
    {
        $this->_TurnService = $TurnService;
    }

    public function index(Request $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $data = $service->getList($request->route('microsite_id'), $request->input('with'), $request->input('type_turn'));
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function show(Request $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->get($request->route('microsite_id'), $request->route('turn_id'), $request->input('with'));
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function create(TurnRequest $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->create($request, $request->route('microsite_id'), $request->_bs_user_id);
            if ($result["response"] == "ok") {
                return $this->CreateJsonResponse(true, 201, "");
            } else {
                return $this->CreateJsonResponse(true, 401, "Conflictos con otras fechas", $result["data"]);
            }
        });
    }

    public function delete(Request $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->delete($request->route('microsite_id'), $request->route('turn_id'));
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function update(TurnRequest $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->update($request, $request->route('microsite_id'), $request->_bs_user_id);
            if ($result["response"] == "ok") {
                return $this->CreateJsonResponse(true, 201, "", null);
            } else {
                return $this->CreateJsonResponse(true, 401, "", $result["data"], null, null, "Conflictos con otras fechas");
            }
        });
    }

    public function search(Request $request)
    {
/* evaluando su eliminacion */
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->search($request->route('microsite_id'), $request->input());
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function unlinkZone(Request $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $service->unlinkZone($request->route('microsite_id'), $request->route('turn_id'), $request->route('zone_id'));
            return $this->CreateResponse(true, 200);
        });
    }

    public function listTableZone(Request $request)
    {
        $service = $this->_TurnService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->getListTable($request->route('turn_id'), $request->route('zone_id'));
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

}
