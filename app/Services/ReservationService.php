<?php

namespace App\Services;
use App\res_reservation;

class ReservationService {

    public function getList(int $microsite_id, $date = null) {
        //return [$microsite_id,$date];
        $rows = res_reservation::where('ms_microsite_id', $microsite_id)
                ->where('date_reservation', $date)
                ->get();

        $response = array();
        $i = 0;
        foreach ($rows as $row) {
            $response["id"] = $row->id;
            $response["date_reservation"] = $row->date_reservation;
            $response["hours_reservation"] = $row->hours_reservation;
            $response["hours_duration"] = $row->hours_duration;
            $response["num_people"] = $row->num_people;
            $response["status_release"] = $row->status_released;
            $response["total"] = $row->total;
            $response["consume"] = $row->consume;
            $response["num_table"] = $row->num_table;
            $response["colaborator"] = $row->colaborator;
            $response["note"] = $row->note;
            $response["type_reservation"] = $row->type_reservation;
            $response["email"] = $row->email;
            $response["phone"] = $row->phone;
            $response["res_guest_id"] = $row->res_guest_id;
            $response["res_reservation_status_id"] = $row->res_reservation_status_id;
            $i++;
        }

        return $response;
    }
   

}
