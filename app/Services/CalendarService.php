<?php

namespace App\Services;

use App\res_turn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Domain\Calendar;
use App\res_turn_calendar;
use App\res_turn;

class CalendarService
{

    public function getList(int $microsite_id, int $year, int $month, int $day = null)
    {

        $calendar = new Calendar($year, $month, $day);

        $turns = res_turn_calendar::
        whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
            ->where('start_date', '<=', $calendar->FIRST_DATE)
            ->where('end_date', '>=', $calendar->FIRST_DATE)
            ->orWhere(function ($query) use ($calendar) {
                $query
                    ->where('start_date', '<=', $calendar->END_DATE)
                    ->where('end_date', '>=', $calendar->END_DATE);
            })
            ->orWhere(function ($query) use ($calendar) {
                $query
                    ->whereRaw('case when start_date = end_date and start_date between \'' . $calendar->FIRST_DATE . '\' and \'' . $calendar->END_DATE . '\' then 1 else 0 end');
            })
            ->get()->map(function ($item) {
                return (object)[
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

    public function create(int $microsite_id, int $res_turn_id, string $date)
    {
        $res_turn = res_turn::findOrFail($res_turn_id);

        list($year, $month, $day) = explode("-", $date);
        $turns = $this->getList($microsite_id, $year, $month, $day);

        $valid = true;
        foreach ($turns as $turn) {
            $p = (strtotime($res_turn->hours_ini) > strtotime($turn['start_time']) && strtotime($res_turn->hours_ini) < strtotime($turn['end_time']));
            $q = (strtotime($res_turn->hours_end) > strtotime($turn['start_time']) && strtotime($res_turn->hours_end) < strtotime($turn['end_time']));
            if ($p || $q) {
                $valid = false;
            }
        }
//        var_dump($p);var_dump($q);
//        var_dump($valid);
        if (!$valid) {
            abort(406, 'Este horario se cruza con un horario existente. Por favor cambie las horas o escoja un horario diferente.');
        }

        $res_turn_calendar = new res_turn_calendar();
        $res_turn_calendar->res_turn_id = $res_turn_id;
        $res_turn_calendar->start_date = $date;
        $res_turn_calendar->end_date = $date;
        $res_turn_calendar->start_time = $res_turn->hours_ini;
        $res_turn_calendar->end_time = $res_turn->hours_end;
        $res_turn_calendar->date_add = Carbon::now();
        $res_turn_calendar->user_add = 1;
        $res_turn_calendar->save();

    }

    public function getListShift(int $microsite_id, string $date)
    {
        list($year, $month, $day) = explode("-", $date);
        $turns = $this->getList($microsite_id, $year, $month, $day);

        $tipeturns = \App\res_type_turn::where('status', 1)->get()->map(function ($item) use ($turns) {
            foreach ($turns as $value) {
                $turn = $value['turn'];
                if (@$turn['type_turn'] && $turn['type_turn']['id'] == $item->id) {
                    unset($turn['type_turn']);
                    $item->turn = $turn;
                }
            }
            $item->turn = !empty($item->turn) ? $item->turn : null;
            return $item;
        });

        return $tipeturns;
    }


    public function deleteCalendar(int $res_turn_id, string $date)
    {
        DB::Transaction(function () use ($res_turn_id, $date) {
            $count = res_turn_calendar::where('start_date', $date)
                ->where('end_date', $date)->where('res_turn_id', $res_turn_id)->count();
            if ($count > 0) {
                DB::Table('res_turn_calendar')->where('start_date', $date)
                    ->where('end_date', $date)->where('res_turn_id', $res_turn_id)->delete();
            } else {
                $count = res_turn_calendar::where('start_date', $date)
                    ->where('res_turn_id', $res_turn_id)->count();
                if ($count > 0) {
                    DB::Table('res_turn_calendar')->where('start_date', $date)
                        ->where('res_turn_id', $res_turn_id)->update([
                            'start_date' => Carbon::createFromFormat('Y-m-d', $date)->addDays(7)
                        ]);
                } else {
                    $res_turn_calendar_aux = res_turn_calendar::where('res_turn_id', $res_turn_id)->whereRaw('weekday(start_date) = weekday(\'' . $date . '\')')->orderBy('start_date', 'desc')->first();
                    DB::Table('res_turn_calendar')->where('start_date', $res_turn_calendar_aux->start_date)
                        ->where('res_turn_id', $res_turn_calendar_aux->res_turn_id)->update([
                            'end_date' => Carbon::createFromFormat('Y-m-d', $date)->addDays(-7)
                        ]);
                    $res_turn_calendar = new res_turn_calendar();
                    $res_turn_calendar->res_turn_id = $res_turn_calendar_aux->res_turn_id;
                    $res_turn_calendar->start_date = Carbon::createFromFormat('Y-m-d', $date)->addDays(7);
                    $res_turn_calendar->end_date = $res_turn_calendar_aux->end_date;
                    $res_turn_calendar->end_time = $res_turn_calendar_aux->end_time;
                    $res_turn_calendar->date_add = Carbon::now();
                    $res_turn_calendar->user_add = 1;
                    $res_turn_calendar->save();
                }
            }
        });

    }

    public function existConflictTurn($turn_id, $start_time, $end_time)
    {
        $date = date('Y-m-d');
//        $data = DB::select(
//                        "SELECT c.res_type_turn_id, c.start_date, c.end_date, DATE_FORMAT(c.start_date, '%w') as weekday, c.start_time, c.end_time, c.res_turn_id "
//                        . "FROM res_turn_calendar as c "
//                        . "WHERE (c.start_date >= $date OR c.end_date = '9999-12-31') "
//                        . "ORDER BY weekday ASC");
//        
//        $data = DB::select(
//                        "SELECT c.res_type_turn_id, c.start_date, c.end_date, DATE_FORMAT(c.start_date, '%w') as weekday, c.start_time, c.end_time, c.res_turn_id "
//                        . "FROM res_turn_calendar as c "
//                        . "WHERE (c.start_date >= $date OR c.end_date = '9999-12-31') "
//                        . "ORDER BY weekday ASC");
//        foreach ($array as $key => $value) {
//            
//        }


        $data = res_turn_calendar::where(function ($query) use ($date) {
            $query->where('end_date', '>=', $date)->orWhere('end_date', '9999-12-31');
        })->orderBy('start_date')->get();


        return $data;

    }

}
