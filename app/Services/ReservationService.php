<?php

namespace App\Services;
use App\res_reservation;
use App\res_guest;
use App\res_table;
use App\res_table_reservation;
use App\Entities\Block;
use App\Entities\BlockTable;
Use DB;
Use Exception;

class ReservationService {

    protected $_ZoneTableService;

    public function __construct(GuestService $GuestService) {
        $this->_GuestService = $GuestService;
    }

    public function getList(int $microsite_id, string $date = null) {
        //return [$microsite_id,$date];

        //$rows = Block::where('ms_microsite_id', $microsite_id)->with('tables')->get();
        $rowsBlocked = BlockTable::with('block')->get();
        $response_b = array();
        $b = 0;
        foreach ($rowsBlocked as $row) {
            $response_b[$b]["res_reservation_id"] = "";
            $response_b[$b]["start_time"] = $row->block->start_time;
            $response_b[$b]["end_time"] = $row->block->end_time;
            $response_b[$b]["num_people"] = "";
            $response_b[$b]["first_name"] = "";
            $response_b[$b]["last_name"] = "";
            $response_b[$b]["res_table_id"] = $row->res_table_id;
            $response_b[$b]["res_block_id"] = $row->res_block_id;
            
            $b++;
        }


        $rowsReservation = res_reservation::where('ms_microsite_id', $microsite_id)
                ->where('date_reservation', $date)
                ->with('guest')
                ->with('tables')
                ->get();
        
        $response = array();
        $i = 0;
        foreach ($rowsReservation as $row) {
            $response[$i]["res_reservation_id"] = $row->id;
            //$response[$i]["date_reservation"] = $row->date_reservation;
            $response[$i]["start_time"] = $row->hours_reservation;
            $response[$i]["end_time"] = "";
            //$response[$i]["hours_duration"] = $row->hours_duration;
            $response[$i]["num_people"] = $row->num_people;
            //$response[$i]["status_release"] = $row->status_released;
            //$response[$i]["total"] = $row->total;
            //$response[$i]["consume"] = $row->consume;
            //$response[$i]["num_table"] = $row->num_table;
            //$response[$i]["colaborator"] = $row->colaborator;
            //$response[$i]["note"] = $row->note;
            //$response[$i]["type_reservation"] = $row->type_reservation;
            //$response[$i]["email"] = $row->email;
            //$response[$i]["phone"] = $row->phone;
            //$response[$i]["res_guest_id"] = $row->res_guest_id;
            //$response[$i]["res_reservation_status_id"] = $row->res_reservation_status_id;
            $response[$i]["first_name"] = $row->guest->first_name;
            $response[$i]["last_name"] = $row->guest->last_name;
            $response[$i]["res_table_id"] = $row->tables->res_table_id;
            $response[$i]["res_block_id"] = "";
            
            $i++;
        }

        $resultado_f = array_merge($response, $response_b);
        
        return $resultado_f;
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

    public function update(array $data, int $microsite_id, int $reservation_id, int $user_id) {
        DB::BeginTransaction();
        try {
            $reservation = res_reservation::where('id', $reservation_id)->where('ms_microsite_id', $microsite_id)->first();
            if ($reservation == NULL) {
                throw new Exception('messages.block_not_exist_turn');
            }

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

            if (!$reservation->save()) {
                throw new Exception('messages.reservation_error_update');
            }

            DB::commit();
            $response["mensaje"] = "messages.reservation_update_success";
            $response["estado"] = true;

        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }
        return (object) $response;
    }

    public function delete(int $microsite_id, int $reservation_id) {
        DB::BeginTransaction();
        try {
            $reservation = new res_reservation();
            
            $reservation->where('id', $reservation_id)->where('ms_microsite_id', $microsite_id)->update(["res_reservation_status_id" => 2]);
            DB::Commit();
            $response["mensaje"] = "messages.reservation_update_success";
            $response["estado"] = true;
            //return true;
        } catch (\Exception $e) {
            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
            //abort(500, $e->getMessage());
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
