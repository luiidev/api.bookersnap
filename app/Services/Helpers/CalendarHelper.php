<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services\Helpers;

/**
 * Description of CalendarHelper
 *
 * @author DESKTOP-BS01
 */
use App\res_turn_calendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarHelper {
    
    static function realDate(int $microsite_id) {
        $now = Carbon::now();
        $yesterday = $now->copy()->yesterday();
        $dayOfWeek = $yesterday->dayOfWeek + 1;
                
        $turnYesterday = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
//                            DB::raw("dayofweek(res_turn_calendar.start_date) as dayOfWeek"),
                            DB::raw("CONCAT('" . $yesterday->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $yesterday->toDateString())
                        ->where("end_date", ">=", $yesterday->toDateString())
                        ->orderBy("end_datetime", 'DESC')->limit(1)->first();
        
        if ($turnYesterday) {
            $endDatetime = Carbon::parse($turnYesterday->end_datetime);
            $inDate = $endDatetime->toDateString() <=> $now->toDateString();
            $inDateTime = $endDatetime->toDateTimeString() <=> $now->toDateTimeString();
            if($inDate == 0 && $inDateTime != -1){
                return $now->copy()->yesterday();
            }
        }        
        return $now;
    }
    
    static function searchDate(int $microsite_id, $date) {
        
        $dateIn = Carbon::parse($date);
        $now = Carbon::now();
        $esUnaFechaMayor = (($dateIn->toDateString() <=> $now->toDateString()) === 1);
        if($esUnaFechaMayor){
            $now = $dateIn;
        }
        
        $yesterday = $now->copy()->yesterday();
        $dayOfWeek = $yesterday->dayOfWeek + 1;
                
        $turns = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            DB::raw("CONCAT('" . $yesterday->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $yesterday->toDateString() . "',' ',end_time), CONCAT('" . $now->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $yesterday->toDateString())
                        ->where("end_date", ">=", $yesterday->toDateString())
                        ->orderBy("end_datetime", 'DESC')->limit(1)->first();
        
        if ($turns) {
            $endDatetime = Carbon::parse($turns->end_datetime);
            $inDate = $endDatetime->toDateString() <=> $now->toDateString();
            $inDateTime = $endDatetime->toDateTimeString() <=> $now->toDateTimeString();
            if($inDate == 0 && $inDateTime != -1){
                return $now->copy()->yesterday();
            }
        }
        
        
        $date = $now->toDateString();
        $dayofweek = $now->dayOfWeek + 1;
        
        $turns = res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            DB::raw("dayofweek(start_date) AS dayofweek"),
                            DB::raw("IF(start_date < '$date', "
                                    . "ADDDATE('$date', INTERVAL IF(dayofweek(start_date) >= $dayofweek, (dayofweek(start_date) - $dayofweek), 7 + (dayofweek(start_date)-$dayofweek)) DAY), "
                                    . "start_date) AS date_ini"),
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            "start_date",
                            "end_date"
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where(function($query) use ($now){
                            return $query->where("start_date", "<=", $now->toDateString())
                                    ->where("end_date", ">=", $now->toDateString())
                                ->orWhere("start_date", ">=", $now->toDateString());
                        })->orderBy("date_ini")->limit(1)->first();
                        
        if($turns){
            return Carbon::parse($turns->date_ini. " 00:00:00");
        }
                
        return $now;
    }
    
    static function turnsDayCalendar(int $microsite_id, string $date) {
        $now = Carbon::parse($date. " 00:00:00");
        $nextday = $now->copy()->addDay();
        $dayOfWeek = $now->dayOfWeek + 1;
        return res_turn_calendar::select(array(
                            "res_turn.id",
                            "res_turn_calendar.res_type_turn_id",
                            "res_turn.hours_ini",
                            "res_turn.hours_end",
                            DB::raw("CONCAT('" . $now->toDateString() . "',' ',start_time) AS start_datetime"),
                            DB::raw("IF(end_time > start_time, CONCAT('" . $now->toDateString() . "',' ',end_time), CONCAT('" . $nextday->toDateString() . "',' ',end_time)) AS end_datetime")
                        ))
                        ->join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                        ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                        ->where("res_turn.ms_microsite_id", $microsite_id)
                        ->where("start_date", "<=", $now->toDateString())
                        ->where("end_date", ">=", $now->toDateString())
                        ->orderBy("start_datetime")->get();
    }
    
}
