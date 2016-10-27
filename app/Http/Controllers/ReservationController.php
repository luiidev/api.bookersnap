<?php

namespace App\Http\Controllers;

use App\Helpers\MailMandrillHelper;
use App\Http\Controllers\Controller as Controller;
use App\Http\Requests\ReservationRequest;
use App\Services\ReservationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    protected $_ReservationService;
    protected $_MailMandrillHelper;

    public function __construct(ReservationService $ReservationService)
    {
        $this->_ReservationService = $ReservationService;
        $this->_MailMandrillHelper = new MailMandrillHelper('gOPLZL8WNLUaeY2CsRmckQ');
    }

    public function index(Request $request)
    {
        $service = $this->_ReservationService;

        return $this->TryCatch(function () use ($request, $service) {
            $date = Carbon::now()->setTimezone($request->timezone);
            $date = $date->format('Y-m-d');
            $data = $service->getList($request->route('microsite_id'), $date);
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
            $messageData['text']    = $request->input('mensaje');

            $response = $this->_MailMandrillHelper->sendEmail($messageData, 'emails.reservation-cliente');

            return $this->CreateResponse(true, 200, "", $response);
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

}
