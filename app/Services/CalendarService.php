<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Domain\Calendar;
use App\res_turn_calendar;

class CalendarService {

    public function getList(int $microsite_id, int $year, int $month, int $day = null) {

        $calendar = new Calendar($year, $month, $day);
        
//        $turns = res_turn_calendar::where('end_date', '!=', '9999-12-31')->orWhere('start_date', '>=', $calendar->END_DATE)->get();
        

//        $turns = res_turn_calendar::where(function($query) use ($calendar){
//            $query->where('end_date', '!=', "'9999-12-31'")->orWhere('start_date', '>=', "'$calendar->END_DATE'")->orWhere(function($query) use($calendar){
//                $query->where('end_date', '!=', "'9999-12-31'")->where('end_date', '>=', "'$calendar->FIRST_DATE'")->where('start_date', '>=', "'$calendar->END_DATE'");
//            });
//        })->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')->get();
        
//        return $turns;
        
//        $turns = res_turn_calendar::where('start_date', '>=', $calendar->FIRST_DATE)->where('start_date', '<=', $calendar->END_DATE)->where(function($query) use ($calendar) {
//                    return $query->where('end_date', '<=', $calendar->END_DATE)->orWhere('end_date', '=', '9999-12-31');
//                })->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')->with(['turn.typeTurn'])->get()->map(function($item) {
//            return (object) [
//                        'title' => $item->turn->name,
//                        'start_time' => $item->turn->hours_ini,
//                        'end_time' => $item->turn->hours_end,
//                        'color' => $item->turn->typeTurn->color,
//                        'start_date' => $item->start_date,
//                        'end_date' => $item->end_date,
//                        'turn' => $item->turn
//            ];
//        });
        

//        if ($day == null) {
//            $turns = DB::select("SELECT t.name AS title, tt.color,
//                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_ini, ' ini'), c.start_time) AS start_time, 
//                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_end, ' iend'), c.end_time) AS end_time,
//                                    c.res_turn_id, t.res_type_turn_id, 
//                                    c.start_date, c.end_date                                    
//                                    FROM res_turn_calendar AS c
//                                    INNER JOIN res_turn AS t ON t.id = c.res_turn_id
//                                    INNER JOIN res_type_turn AS tt ON tt.id = t.res_type_turn_id
//                                    WHERE t.ms_microsite_id = ? 
//                                    AND c.start_date >= $calendar->FIRST_DATE  
//                                    AND (c.end_date <= $calendar->END_DATE OR c.end_date = '9999-12-31')", [$microsite_id]);
//        } else {            
//            $turns = DB::select("SELECT t.name AS title, tt.color, 
//                                    t.hours_ini AS start_time, t.hours_end AS end_time, c.res_turn_id, t.res_type_turn_id, 
//                                    c.start_date, c.start_date as end_date
//                                    FROM res_turn_calendar AS c
//                                    INNER JOIN res_turn AS t ON t.id = c.res_turn_id
//                                    INNER JOIN res_type_turn AS tt ON tt.id = t.res_type_turn_id
//                                    WHERE t.ms_microsite_id = ? AND c.start_date = ?", [$microsite_id, $calendar->NOW_DATE]);
//        }

        $turns = DB::select("SELECT t.name AS title, tt.color,
                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_ini, ' ini'), c.start_time) AS start_time, 
                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_end, ' iend'), c.end_time) AS end_time,
                                    c.res_turn_id, t.res_type_turn_id, 
                                    c.start_date, c.end_date                                    
                                    FROM res_turn_calendar AS c
                                    INNER JOIN res_turn AS t ON t.id = c.res_turn_id
                                    INNER JOIN res_type_turn AS tt ON tt.id = t.res_type_turn_id
                                    WHERE t.ms_microsite_id = ? 
                                    AND ( c.end_date != '9999-12-31' OR c.start_date >= $calendar->END_DATE OR (c.end_date != '9999-12-31' AND c.end_date >= $calendar->FIRST_DATE AND c.start_date >= $calendar->END_DATE) )", [$microsite_id]);
                
        
        foreach ($turns as $turn) {
            $calendar->generateByWeekDay($turn, $turn->start_date, $turn->end_date);
        }
        return $calendar->get();
    }

    public function create(array $data, int $microsite_id) {
        
    }

}
