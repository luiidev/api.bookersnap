<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalendarService;

class CalendarController extends Controller {

    protected $_CalendarService;

    public function __construct(CalendarService $CalendarService) {
        $this->_CalendarService = $CalendarService;
    }

    public function index(Request $request) {
        
        $service = $this->_CalendarService;
        return $this->TryCatch(function () use ($request, $service) {
                    $param = explode("-", $request->route('date'));
                    list($year, $month) = $param;
                    $day = isset($param[2]) ? $param[2] : null;
                    $data = $service->getList($request->route('microsite_id'), $year, $month, $day);
                    return $this->CreateResponse(true, 201, "", $data);
                });
    }

}
