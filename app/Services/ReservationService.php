<?php

namespace App\Services;
use App\res_reservation;
use App\res_guest;
Use DB;
Use Exception;

class ReservationService {

    protected $_ZoneTableService;

    public function __construct(GuestService $GuestService) {
        $this->_GuestService = $GuestService;
    }

    public function getList(int $microsite_id, string $date = null) {
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

    public function create(array $data, int $microsite_id, int $user_id) {
        DB::beginTransaction();
        try {
            $reservation = new res_reservation();
            $reservation->email = $data["email"];
            $reservation->ms_microsite_id = $microsite_id;
            $reservation->phone = $data["phone"];
            $reservation->date_reservation = date("Y-m-d",strtotime($data["date_reservation"]));
            $reservation->hours_reservation = date("Y-m-d",strtotime($data["hours_reservation"]));
            $reservation->hours_duration = date("h:i:s",strtotime($data["hours_duration"]));
            $reservation->num_people = $data["num_people"];
            $reservation->note = $data["note"];
            $reservation->res_reservation_status_id = 1;    
            $reservation->user_add = $user_id;
            $reservation->date_add = \Carbon\Carbon::now();
            $reservation->date_upd = $reservation->date_add;

            $guest_id = res_guest::find($data["res_guest_id"]);
            if($guest_id==NULL){
                $data_guest=["first_name"=>$data["first_name"],"last_name"=>$data['last_name']];              
                $res_guest_id = $this->createGuest($data_guest, $microsite_id, $user_id);
                $reservation->res_guest_id = $res_guest_id;

            }else{
                $reservation->res_guest_id = $data["res_guest_id"];
            }            

            if(!$reservation->save()) {
                throw new Exception('messages.reservation_save_error');
            }
            //dd($reservation);
            DB::Commit();
            $response["mensaje"] = "messages.reservation_create_success";
            $response["estado"] = true;

        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }
        return (object) $response;
    }

    public function createGuest(array $data, int $microsite_id, int $user_id) {
         try {
            $guest = new res_guest();
            $guest->first_name = $data['first_name'];
            $guest->last_name = empty($data['last_name']) ? null : $data['last_name'];
            $guest->ms_microsite_id = $microsite_id;
            $guest->user_add = $user_id;
            $guest->date_add = \Carbon\Carbon::now();

            $guest->save();
            return $guest->id;

        } catch (\Exception $e) {

            abort(500, "Ocurrio un error interno");
        }
    }
   

}
