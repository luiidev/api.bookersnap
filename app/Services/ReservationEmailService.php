<?php

namespace App\Services;

use App\res_reservation_email;

class ReservationEmailService
{

    public function create(array $data)
    {

        $reservation_email                     = new res_reservation_email();
        $reservation_email->subject            = $data['subject'];
        $reservation_email->message            = $data['message'];
        $reservation_email->res_reservation_id = $data['res_reservation_id'];
        $reservation_email->user_add           = $data['user_add'];
        $reservation_email->date_add           = $data['date_add'];
        $reservation_email->save();

        return $reservation_email;

    }

}
