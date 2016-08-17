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
        return $this->TryCatch(function () use ($microsite_id) {
            $data = $this->_GuestService->getList();
            return $this->CreateResponse(true, 201, "", $data);
        });
    }


}
