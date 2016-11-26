<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use App\Http\Requests\ServerCreateRequest;
use App\Http\Requests\ServerUpdateRequest;
use App\Services\ServerService;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Services\TableService;

class TableController extends Controller {

    protected $_TableService;

    public function __construct(TableService $TableService) {
        $this->_TableService = $TableService;
    }

    function availability(Request $request) {
        $service = $this->_TableService;
        return $this->TryCatch(function () use ($request, $service) {
                    $date = Carbon::now()->setTimezone($request->timezone);
                    $date = $request->input('date', $date->format('Y-m-d'));
                    $data = $service->availability($request->route('microsite_id'), $date);
//            $this->_notification($microsite, $data->server, "Se elimino un servidor", "delete", $request->key);
                    return $this->CreateJsonResponse(true, 200, "disponibilidad de mesas", $data);
                });
    }

}
