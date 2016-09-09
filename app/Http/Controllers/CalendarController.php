<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarRequest;
use Illuminate\Http\Request;
use App\Services\CalendarService;

class CalendarController extends Controller
{

    protected $_CalendarService;

    public function __construct(CalendarService $CalendarService)
    {
        $this->_CalendarService = $CalendarService;
    }

    public function index(Request $request)
    {

        $service = $this->_CalendarService;
        return $this->TryCatch(function () use ($request, $service) {
            $param = explode("-", $request->route('date'));
            list($year, $month) = $param;
            $day = isset($param[2]) ? $param[2] : null;
            $data = $service->getList($request->route('microsite_id'), $year, $month, $day);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }


    public function listShift(Request $request)
    {

        $service = $this->_CalendarService;
        return $this->TryCatch(function () use ($request, $service) {
            $data = $service->getListShift($request->route('microsite_id'), $request->route('date'));
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function storeCalendar($lang, $microsite_id, CalendarRequest $request)
    {
        $res_turn_id = $request->input('res_turn_id');
        $date = $request->input('date');
        return $this->TryCatch(function () use ($microsite_id, $res_turn_id, $date) {
            $this->_CalendarService->create($microsite_id, $res_turn_id, $date);
            return $this->CreateResponse(true, 201);
        });
    }

    public function deleteCalendar($lang, $microsite_id, Request $request)
    {
        $res_turn_id = $request->input('res_turn_id');
        $date = $request->input('date');
        return $this->TryCatch(function () use ($res_turn_id, $date) {
            $this->_CalendarService->deleteCalendar($res_turn_id, $date);
            return $this->CreateResponse(true, 200);
        });
    }

}
