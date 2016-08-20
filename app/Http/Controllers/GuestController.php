<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Services\GuestService;
use App\Http\Requests\GuestRequest;
use App\Services\GuestTagCategoryService;

class GuestController extends Controller {

    protected $_GuestService;

    public function __construct(GuestService $GuestService) {
        $this->_GuestService = $GuestService;
    }

    public function index(Request $request) {
        $microsite_id = $request->route('microsite_id');
        $params = $request->input();
        return $this->TryCatch(function () use ($microsite_id, $params) {
                    $data = $this->_GuestService->getList($microsite_id, $params);
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

    public function show(Request $request) {
        $microsite_id = $request->route('microsite_id');
        $guest_id = $request->route('guest_id');
        return $this->TryCatch(function () use ($microsite_id, $guest_id) {
                    $result = $this->_GuestService->get($microsite_id, $guest_id);
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function create(GuestRequest $request) {
        $microsite_id = $request->route('microsite_id');
        return $this->TryCatch(function () use ($request, $microsite_id) {
                    $result = $this->_GuestService->create($request->all(), $microsite_id);
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function update(GuestRequest $request) {
        $microsite_id = $request->route('microsite_id');
        $guest_id = $request->route('guest_id');
        return $this->TryCatch(function () use ($request, $microsite_id, $guest_id) {
                    $result = $this->_GuestService->update($request->all(), $guest_id);
                    return response()->json($result);
                });
    }
    
    public function reservation(Request $request) {
        $microsite_id = $request->route('microsite_id');
        $guest_id = $request->route('guest_id');
        $params = $request->input();
        return $this->TryCatch(function () use ($microsite_id, $guest_id, $params) {
                    $result = $this->_GuestService->reservation($microsite_id, $guest_id, $params);
                    return response()->json($result);
                });
    }

}
