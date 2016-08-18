<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\Input;
use App\Services\GuestService;
use App\Http\Requests\GuestRequest;
use App\Services\GuestTagCategoryService;

class GuestController extends Controller {

    protected $_GuestService;
    protected $_GuestTagCategoryService;

    public function __construct(GuestService $GuestService, GuestTagCategoryService $GuestTagCategoryService) {
        $this->_GuestService = $GuestService;
        $this->_GuestTagCategoryService = $GuestTagCategoryService;
    }

    public function index($lang, int $microsite_id) {
        $params = Input::get();
        return $this->TryCatch(function () use ($microsite_id, $params) {
                    $data = $this->_GuestService->getList($microsite_id, $params);
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

    public function show($lang, int $microsite_id, int $guest_id) {
        return $this->TryCatch(function () use ($microsite_id, $guest_id) {
                    $result = $this->_GuestService->get($microsite_id, $guest_id);
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function create(GuestRequest $request, $lang, int $microsite_id) {
        return $this->TryCatch(function () use ($request, $microsite_id) {
                    $result = $this->_GuestService->create($request->all(), $microsite_id);
                    return $this->CreateResponse(true, 201, "", $result);
                });
    }

    public function update(GuestRequest $request, $lang, int $microsite_id, int $id) {

        return $this->TryCatch(function () use ($request, $microsite_id, $id) {
                    $result = $this->_GuestService->update($request->all(), $id);
                    return response()->json($result);
                });
    }

    public function form($lang, int $microsite_id, int $guest_id) {

        return $this->TryCatch(function () use ($microsite_id, $guest_id) {
                    $guest = $this->_GuestService->get($microsite_id, $guest_id);
                    $tags = $this->_GuestTagCategoryService->getList();
                    return response()->json(array("guest" => $guest, "select_tags" => $tags));
                });
    }

}
