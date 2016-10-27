<?php

namespace App\Http\Controllers;

use App\Helpers\MailMandrillHelper;
use App\Http\Controllers\Controller as Controller;
use App\Http\Requests\ZoneRequest;
use App\Services\ZoneService;
use Illuminate\Http\Request;

class ZoneController extends Controller
{

    protected $_ZoneService;
    protected $_MailMandrillHelper;

    public function __construct(ZoneService $ZoneService)
    {
        $this->_ZoneService        = $ZoneService;
        $this->_MailMandrillHelper = new MailMandrillHelper('gOPLZL8WNLUaeY2CsRmckQ');
    }

    public function index(Request $request)
    {
        $service = $this->_ZoneService;

        $messageData['from_email'] = "user@bookersnap.com";
        $messageData['from_name']  = "bookersnap.com";
        $messageData['subject']    = "Zonas";
        $messageData['text']       = "Esta chbre";
        $messageData['to_email']   = "joper30@gmail.com";
        $messageData['to_name']    = "josue diaz";

        $this->_MailMandrillHelper->sendEmail($messageData, 'emails.reservation-cliente');

        return $this->TryCatch(function () use ($request, $service) {
            $data = $service->getList($request->route('microsite_id'), $request->input('with'));
            return $this->CreateResponse(true, 200, "", $data);
        });

    }

    public function show(Request $request)
    {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->get($request->route('microsite_id'), $request->route('zone_id'), $request->input('with'));
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

    public function create(ZoneRequest $request)
    {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->create($request->all(), $request->route('microsite_id'), $request->input('_bs_user_id'));
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function update(ZoneRequest $request)
    {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->update($request->all(), $request->route('zone_id'), $request->input('_bs_user_id'));
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

    public function delete(Request $request)
    {
        $service = $this->_ZoneService;

        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->delete($request->route('microsite_id'), $request->route('zone_id'));
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

    public function listTable(Request $request)
    {
        $service = $this->_ZoneService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->getListTable($request->route('microsite_id'), $request->route('zone_id'));
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

}
