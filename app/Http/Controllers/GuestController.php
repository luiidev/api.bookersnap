<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Input;

use App\Services\GuestService;

class GuestController extends Controller
{
    protected $_GuestService;
 
    public function __construct(GuestService $GuestService) {
        $this->_GuestService = $GuestService;
    }

    public function index($lang, int $microsite_id){

        $params = Input::get();

        return $this->TryCatch(function () use ($microsite_id,$params) {
            $data = $this->_GuestService->getList($microsite_id,$params);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function show($lang, int $microsite_id,int $guest_id){
        return $this->TryCatch(function () use ($microsite_id,$guest_id) {
            $result = $this->_GuestService->get($microsite_id,$guest_id);
            return $this->CreateResponse(true, 201, "", $result);
        });
    }


}
