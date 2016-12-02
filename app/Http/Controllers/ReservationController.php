<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Helpers\MailMandrillHelper;
use App\Http\Controllers\Controller as Controller;
use App\Http\Requests\ReservationRequest;
use App\Services\ReservationEmailService;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    protected $_ReservationService;
    protected $_ReservationEmailService;
    protected $_MailMandrillHelper;

    public function __construct(ReservationService $ReservationService, ReservationEmailService $ReservationEmailService)
    {
        $this->_ReservationService      = $ReservationService;
        $this->_ReservationEmailService = $ReservationEmailService;
        $this->_MailMandrillHelper      = new MailMandrillHelper('gOPLZL8WNLUaeY2CsRmckQ');
    }

    public function index(Request $request)
    {
        $service = $this->_ReservationService;

        return $this->TryCatch(function () use ($request, $service) {
            $date       = Carbon::now();
            $start_date = ($request->input('date')) ? $request->input('date') : $date->toDateString();
            $end_date   = ($request->input('date_end')) ? $request->input('date_end') : $date->toDateString();

            $data = $service->getList($request->route('microsite_id'), $start_date, $end_date);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function search(Request $request)
    {
        $service = $this->_ReservationService;

        return $this->TryCatch(function () use ($request, $service) {
            $date                   = Carbon::now();
            $params                 = $request->all();
            $params['date']         = ($request->input('date')) ? $request->input('date') : $date->format('Y-m-d');
            $params['date_end']     = ($request->input('date_end')) ? $request->input('date_end') : $params['date'];
            $params['page_size']    = ($request->input('page_size')) ? $request->input('page_size') : 0;
            $params['search_text']  = ($request->input('search_text')) ? $request->input('search_text') : "";
            $params['turns']        = ($request->input('turns')) ? explode(",", $request->input('turns')) : [];
            $params['sources']      = ($request->input('sources')) ? explode(",", $request->input('sources')) : [];
            $params['zones']        = ($request->input('zones')) ? explode(",", $request->input('zones')) : [];
            $params['microsite_id'] = $request->route('microsite_id');

            $data = $service->getListSearch($params);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function show(Request $request)
    {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($request, $service) {

            $data = $service->get($request->route('microsite_id'), $request->route('reservation_id'));
            return $this->CreateResponse(true, 201, "", $data);
        });
    }
    public function create(ReservationRequest $request)
    {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->create($request->all(), $request->route('microsite_id'), $request->_bs_user_id);
            return response()->json($result);
        });
    }

    public function update(ReservationRequest $request)
    {
        //$service = $this->_ReservationService;
        $microsite_id   = $request->route('microsite_id');
        $reservation_id = $request->route('reservation_id');

        return $this->TryCatch(function () use ($request, $microsite_id, $reservation_id) {
            $result = $this->_ReservationService->update($request->all(), $microsite_id, $reservation_id, $request->_bs_user_id);
            return response()->json($result);
        });
    }

    public function delete(Request $request)
    {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($request, $service) {

            $result = $service->delete($request->route('microsite_id'), $request->route('reservation_id'));
            return response()->json($result);
        });
    }

    /**
     * Retorna todos los tipos de estado que puede tener una reservacion
     * @return Collection App\res_reservation_status
     */
    public function listStatus()
    {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($service) {

            $statuses = $service->listStatus();
            return $this->CreateResponse(true, 200, "", $statuses);
        });

    }

    public function sendEmail(Request $request)
    {
        $service = $this->_ReservationService;

        return $this->TryCatch(function () use ($request, $service) {

            $messageData['from_email'] = "user@bookersnap.com";
            $messageData['from_name']  = "bookersnap.com";

            $reservation = $service->get($request->route('microsite_id'), $request->route('reservation_id'));

            if (!$reservation) {
                abort(401, "No existe reservación");
            } else if ($reservation->email == null) {
                abort(401, "La reservación no tiene email");
            }

            $messageData['to_email'] = $reservation->email;
            $messageData['to_name']  = $reservation->guest->first_name . " " . $reservation->guest->last_name;

            $messageData['subject'] = $request->input("subject");
            $messageData['message'] = $request->input('message');

            $response = $this->_MailMandrillHelper->sendEmail($messageData, 'emails.reservation-cliente');

            $messageData['res_reservation_id'] = $reservation->id;
            $messageData['user_add']           = $request->_bs_user_id;
            $this->_ReservationEmailService->create($messageData);

            return $this->CreateResponse(true, 200, "Mensaje enviado", $response);
        });
    }

    /**
     * Retorna todos los tipos de origen de una reservacion
     * @return Collection App\res_source_type
     */
    public function listSourceType()
    {
        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($service) {
            $response = $service->listSourceType();
            return $this->CreateResponse(true, 200, "", $response);
        });
    }

    public function patch(Request $request)
    {

        $service = $this->_ReservationService;
        return $this->TryCatch(function () use ($request, $service) {
            $result = $service->patch($request->all(), $request->route('microsite_id'));

            $this->_notification($request->route("microsite_id"), $result, "Reservación actualizada", "update", $request->key);

            return response()->json($result);
        });
    }

    private function _notification(Int $microsite_id, $data, String $message, String $action, String $key = null)
    {
        event(new EmitNotification("b-mesas-floor-res",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $data,
                'action'       => $action,
                'key'          => $key,
            )
        ));
    }

}
