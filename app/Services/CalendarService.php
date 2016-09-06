<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Domain\Calendar;
use App\res_turn_calendar;

class CalendarService {

    public function getList(int $microsite_id, int $year, int $month, int $day = null) {

        $calendar = new Calendar($year, $month, $day);

        $turns = res_turn_calendar::
                        whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
                        ->where('start_date', '<=', $calendar->FIRST_DATE)
                        ->where('end_date', '>=', $calendar->FIRST_DATE)
                        ->orWhere(function ($query) use ($calendar) {
                            $query
                            ->where('start_date', '<=', $calendar->END_DATE)
                            ->where('end_date', '>=', $calendar->END_DATE);
                        })->get()->map(function ($item) {
            return (object) [
                        'title' => $item->turn->name,
                        'start_time' => $item->turn->hours_ini,
                        'end_time' => $item->turn->hours_end,
                        'color' => $item->turn->typeTurn->color,
                        'start_date' => $item->start_date,
                        'end_date' => $item->end_date,
                        'turn' => $item->turn->toArray()
            ];
        });

        foreach ($turns as $turn) {
            $calendar->generateByWeekDay($turn, $turn->start_date, $turn->end_date);
        }

        if (!is_null($day)) {
            return $calendar->shiftByDay();
        }
        return $calendar->get();
    }

    public function create(array $data, int $microsite_id) {
        
    }

    public function getListShift(int $microsite_id, string $date) {
        list($year, $month, $day) = explode("-", $date);
        $turns = $this->getList($microsite_id, $year, $month, $day);

        $tipeturns = \App\res_type_turn::where('status', 1)->get()->map(function($item) use($turns) {
            foreach ($turns as $value) {
                $turn = $value['turn'];
                if(@$turn['type_turn'] && $turn['type_turn']['id'] == $item->id){
                    unset($turn['type_turn']);
                    $item->turn = $turn;
                }
            }
            $item->turn = !empty($item->turn)?$item->turn:null;
            return $item;
        });
        
        return $tipeturns;
    }

}
