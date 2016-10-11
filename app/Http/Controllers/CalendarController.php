<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarRequest;
use Illuminate\Http\Request;
use App\Services\CalendarService;
use Validator;

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

    public function deleteCalendar($lang, $microsite_id, Request $request, $res_turn_id)
    {
        $date = request('date');
        return $this->TryCatch(function () use ($res_turn_id, $date) {
            $this->_CalendarService->deleteCalendar($res_turn_id, $date);
            return $this->CreateResponse(true, 200);
        });
    }
    
    public function existConflictTurn(Request $request)
    {
        $service = $this->_CalendarService;
        return $this->TryCatch(function () use ($request, $service) {
            $data = $service->existConflictTurn($request->route('turn_id'), $request->route('start_time'), $request->route('end_time'));
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    /**
     * Cambio de turno en el calendario 
     * @param  Illuminate\Http\Request $request 
     * @return  Illuminate\Http\Response  
     */
    public function changeCalendar(Request $request)
    {
        $service = $this->_CalendarService;
        return $this->TryCatch(function () use ($request, $service) {

            $rules = [
                "turn_id"   =>  "required|integer|exists:res_turn,id",
                "shift_id"  =>  "required|integer|exists:res_turn,id",
                "date"       =>  "required|date"
            ];

            if ( Validator::make($request->all(), $rules)->fails()){
                abort(406, "No posee lo campos necesarios o validos para realizar el cambio de turno");
            }

            $service->changeCalendar($request->route("microsite_id"), request("turn_id"), request("shift_id"), request("date"));
            return $this->CreateResponse(true, 201, "");

        });
    }

    /**
     * Retonar la zonas disponible para una fecha
     * @param  string    $lang
     * @param  int    $microsite
     * @param  String    $date      fecha de consulta
     * @return Illuminate\Http\Response      App\res_zone
     */
    public function getZones($lang, $microsite, $date)
    {
        $service = $this->_CalendarService;

        return $this->TryCatch(function () use ($microsite, $date, $service) {

            if (Validator::make(["date" => $date], ["date" => "date"])->fails()){
                abort(406, "La fecha de consulta no es valida");
            }

            $zones = $service->getBlock($microsite, $date);

            return $this->CreateResponse(true, 200, "", $zones);
        });
    }
}
