<?php

namespace App\Services;

use App\Entities\res_table_reservation_temp;
use Carbon\Carbon;
use App\Services\Helpers\CalendarHelper;
use App\Services\Helpers\TurnsHelper;
use App\Services\Helpers\ReservationHelper;
use DB;
use App\Entities\bs_type_event;

class ReservationTemporalService {

    // private $hash          = "test";
    private $timeTolerance = 10;
    private $timezone;
    private $tokenAuth;
    private $configurationService;
    private $availabilityService;

    public function __construct(ConfigurationService $ConfigurationService, AvailabilityService $AvailabilityService) {
        $this->configurationService = $ConfigurationService;
        $this->availabilityService = $AvailabilityService;
    }

    public function createReservationTemporal(int $user_id, int $microsite_id, string $hour, string $date, int $num_guest, int $zone_id = null, string $timezone, int $ev_event_id = null, string $tokenAuth, int $next_day, int $num_guests) {
    
        $this->timezone = $timezone;
        $this->tokenAuth = $tokenAuth;
        $dateExpirePrevious = Carbon::now()->subMinutes($this->timeTolerance)->toDateTimeString();
        
        $reservationTemporal = res_table_reservation_temp::where('ms_microsite_id', $microsite_id)->where('token', $this->tokenAuth)->where('expire', '>=', $dateExpirePrevious)->first();
        
        if ($reservationTemporal) {            
            if(!$this->isDeferer($reservationTemporal, $date, $hour, $num_guest, $zone_id)){                
                $reservationTemporal->expire = $this->datetimeExpire();
                $reservationTemporal->save();
                return $reservationTemporal;                
            }else{                
                $reservationTemporal = $this->updateReservationTemporal($reservationTemporal, $microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
                if (!$reservationTemporal) {
                    $this->deleteReservationTempActive($microsite_id, $this->tokenAuth, $dateExpirePrevious);
                    abort(500, "No hay disponibilidad de mesas");
                }
                return $reservationTemporal;            
            }
        }else{            
            $reservationTemporal = $this->saveReservationTemporal($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone, $user_id, $tokenAuth);
            if (!$reservationTemporal) {
                $this->deleteReservationTempActive($microsite_id, $this->tokenAuth, $dateExpirePrevious);
                abort(500, "No hay disponibilidad de mesas");
            }            
            return $reservationTemporal;
        }
        
    }
    
    private function saveReservationTemporal($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone, $user_id, $tokenAuth) {        
        if (isset($zone_id)) {
            $availabilityList = $this->availabilityService->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
        } else {
            $availabilityList = $this->availabilityService->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $timezone);            
        }
        
        $reservation = $availabilityList[2];
        
        $availability = isset($reservation['availability']) ? $reservation['availability'] : false;
        
        if($availability){
            
            $zone_id = $reservation['form']['zone_id'];
            $tables_id = $this->listTables($reservation['tables_id']);
            $ev_event_id = @$reservation['form']['event_id'];
            
            $reservationTemporal = new res_table_reservation_temp();
            $reservationTemporal->hour = $hour;
            $reservationTemporal->date = $date;
            $reservationTemporal->num_guest = $num_guests;
            $reservationTemporal->zone_id = $zone_id;
            $reservationTemporal->tables_id = $tables_id;            
            $reservationTemporal->ev_event_id = $ev_event_id;
            $reservationTemporal->ms_microsite_id = $microsite_id;
            $reservationTemporal->expire = $this->datetimeExpire();            
            
            $reservationTemporal->user_id = $user_id;
            $reservationTemporal->token = $tokenAuth;            
            $reservationTemporal->save();
            
            return $reservationTemporal;
        }
        return false;
    }
    
    private function updateReservationTemporal(res_table_reservation_temp $reservationTemporal, $microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone) {        
        if (isset($zone_id)) {
            $availabilityList = $this->availabilityService->searchAvailabilityDay($microsite_id, $date, $hour, $num_guests, $zone_id, $next_day, $timezone);
        } else {
            $availabilityList = $this->availabilityService->searchAvailabilityDayAllZone($microsite_id, $date, $hour, $num_guests, $next_day, $timezone);            
        }
        
        $reservation = $availabilityList[2];
        
        $availability = isset($reservation['availability']) ? $reservation['availability'] : false;
        
        if($availability){
            
            $zone_id = $reservation['form']['zone_id'];
            $tables_id = $this->listTables($reservation['tables_id']);
            $ev_event_id = @$reservation['form']['event_id'];
                       
            $reservationTemporal->hour = $hour;
            $reservationTemporal->date = $date;
            $reservationTemporal->num_guest = $num_guests;
            $reservationTemporal->zone_id = $zone_id;
            $reservationTemporal->tables_id = $tables_id;            
            $reservationTemporal->ev_event_id = $ev_event_id;
            $reservationTemporal->ms_microsite_id = $microsite_id;
            $reservationTemporal->expire = $this->datetimeExpire();
                                   
            $reservationTemporal->save();
            
            return $reservationTemporal;
        }
        return false;
    }
    
    
    private function isDeferer($reservationTemporal, $date, $hour, $num_guest, $zone_id) {
        return !($reservationTemporal->hour == $hour && $reservationTemporal->date == $date && $reservationTemporal->num_guest == $num_guest && $reservationTemporal->zone_id == $zone_id);         
    }
    
    private function datetimeExpire() {
        return Carbon::now()->addMinutes($this->timeTolerance)->toDateTimeString();
    }
    
    private function listTables(array $tablesId = null) {
        if(is_array($tablesId)){
            return implode(",", $tablesId);
        }
        return null;
    }
    
    private function deleteReservationTempActive($microsite_id, $token, $dateExpirePrevious) {
        return res_table_reservation_temp::where('ms_microsite_id', $microsite_id)->where('token', $token)->where('expire', '>=', $dateExpirePrevious)->delete();
    }
    
    public function deleteTemporal(string $dateExpirePrevious, string $token) {
        res_table_reservation_temp::with("event")->where('token', $token)->where('expire', '>=', $dateExpirePrevious)->delete();
    }

    public function getTimeTolerance() {
        return $this->timeTolerance;
    }

    public function getTempReservation(String $token) {
        $diff = null;
        $now = Carbon::now();
        $reservationtemporal = res_table_reservation_temp::with(["event" => function($query){
            $condition = "IF(bs_type_event_id = '".bs_type_event::_ID_EVENT_FREE."', CONCAT('".bs_type_event::_BASEURL_IMG_EVENT."', image), CONCAT('".bs_type_event::_BASEURL_IMG_PROMOTION."', image))";
            return $query->select("*", DB::raw("$condition AS image"));
        }])->where("token", $token)->where("expire", ">", $now)->orderBy("id", "desc")->first();
        if ($reservationtemporal !== null) {
            $diff = $now->diffInSeconds(Carbon::parse($reservationtemporal->expire), false) * 1000;
        }
        return array("reservation" => $reservationtemporal, "time" => $diff,);
    }

    public function temporalReserveFinish(String $token)
    {
        $now = Carbon::now();
        $reservationtemporal = res_table_reservation_temp::where("token", $token)->orderBy("expire", "desc")->first();
        if ($reservationtemporal !== null) {
            $reservationtemporal->expire = $now->toDateTimeString();
            $reservationtemporal->save();
        }
    }
}
