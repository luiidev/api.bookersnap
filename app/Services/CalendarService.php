<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Domain\Calendar;

class CalendarService {

    public function getList(int $microsite_id, int $year, int $month, int $day = null) {

        $calendar = new Calendar($year, $month, $day);

        if ($day == null) {
            $turns = DB::select("SELECT t.name, tt.color, c.start_date, c.end_date,
                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_ini, ' ini'), c.start_time) AS start_time, 
                                    IF(TIMESTAMPDIFF(DAY, c.end_date, $calendar->NOW_DATE) > 0, CONCAT(t.hours_end, ' iend'), c.end_time) AS end_time,
                                    c.res_turn_id,
                                    t.res_type_turn_id
                                    FROM res_turn_calendar AS c
                                    INNER JOIN res_turn AS t ON t.id = c.res_turn_id
                                    INNER JOIN res_type_turn AS tt ON tt.id = t.res_type_turn_id
                                    WHERE t.ms_microsite_id = ? AND c.start_date >= $calendar->FIRST_DATE AND (c.end_date <= $calendar->END_DATE OR c.end_date = '9999-12-31')", [$microsite_id]);
        } else {            
            $turns = DB::select("SELECT t.name, tt.color, c.start_date, c.start_date as end_date, t.hours_ini AS start_time, t.hours_end AS end_time, c.res_turn_id, t.res_type_turn_id
                                    FROM res_turn_calendar AS c
                                    INNER JOIN res_turn AS t ON t.id = c.res_turn_id
                                    INNER JOIN res_type_turn AS tt ON tt.id = t.res_type_turn_id
                                    WHERE t.ms_microsite_id = ? AND c.start_date = ?", [$microsite_id, $calendar->NOW_DATE]);
        }       
      
        foreach ($turns as $turn) {
            $calendar->generateByWeekDay(array(
                "title" => $turn->name,
                "color" => $turn->color,
                "start_time" => $turn->start_time,
                "end_time" => $turn->end_time,
            ), $turn->start_date, $turn->end_date);
        }
        
        return $calendar->get();
    }

    public function create(array $data, int $microsite_id) {
        
    }

}
