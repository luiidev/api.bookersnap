<?php

namespace App\Services;

use App\Domain\Calendar;
use App\res_turn;
use App\res_turn_calendar;
use App\res_type_turn;
use App\res_zone;
use App\Services\Helpers\DateTimesHelper;
use Carbon\Carbon;
use DB;

class CalendarService
{

    public function getList(int $microsite_id, int $year, int $month, int $day = null)
    {
        $calendar = new Calendar($year, $month, $day);

        $turns = res_turn_calendar::with("turn")
            ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'title'      => $item->turn->name,
                    // 'start_time' => $item->turn->hours_ini,
                    // 'end_time' => $item->turn->hours_end,
                    'start_time' => $item->start_time,
                    'end_time'   => $item->end_time,
                    'color'      => $item->turn->typeTurn->color,
                    'start_date' => $item->start_date,
                    'end_date'   => $item->end_date,
                    'turn'       => $item->turn->toArray(),
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
        $this->createCalendarHelper(null, $res_turn_id, $date, $microsite_id);
    }

    public function getListShift(int $microsite_id, string $date)
    {
        list($year, $month, $day) = explode("-", $date);
        $turns                    = $this->getList($microsite_id, $year, $month, $day);

        $tipeturns = res_type_turn::where('status', 1)->get()->map(function ($item) use ($turns) {
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
        $now      = Carbon::now();
        $val_date = Carbon::createFromFormat('Y-m-d', $date);
        if ($val_date->lt($now)) {
            abort(401, 'No puede eliminar un turno de una fecha menor a la actual.');
        } else {
            DB::Transaction(function () use ($res_turn_id, $date) {
                $count = res_turn_calendar::where('start_date', $date)
                    ->where('end_date', $date)
                    ->where('res_turn_id', $res_turn_id)
                    ->count();

                if ($count > 0) {
                    DB::Table('res_turn_calendar')->where('start_date', $date)
                        ->where('end_date', $date)
                        ->where('res_turn_id', $res_turn_id)
                        ->delete();
                } else {
                    $count = res_turn_calendar::where('start_date', $date)
                        ->where('res_turn_id', $res_turn_id)
                        ->count();

                    if ($count > 0) {
                        $this->deleteCalendarEquealStartDateCase($res_turn_id, $date);
                    } else {
                        $count = res_turn_calendar::where('end_date', $date)
                            ->where('res_turn_id', $res_turn_id)
                            ->count();

                        if ($count > 0) {
                            $this->deleteCalendarEquealEndDateCase($res_turn_id, $date);
                        } else {
                            $this->deleteCalendarBetweenDatesCase($res_turn_id, $date);
                        }
                    }
                }
            });
        }
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

    /**
     * Cambio de turno en calendario Service
     * @param  int $res_turn_id
     * @param  int $res_shift_id
     * @param  date $date
     * @return Void
     */
    public function changeCalendar(int $microsite_id, $res_turn_id, $res_shift_id, $date)
    {
        $now      = Carbon::now();
        $val_date = Carbon::createFromFormat('Y-m-d', $date);
        if ($val_date->lt($now)) {
            abort(401, 'No puede cambiar un turno de una fecha menor a la actual.');
        } else {
            DB::Transaction(function () use ($microsite_id, $res_turn_id, $res_shift_id, $date) {
                $count = res_turn_calendar::where('start_date', $date)
                    ->where('end_date', $date)
                    ->where('res_turn_id', $res_turn_id)
                    ->count();

                if ($count > 0) {
                    $this->updateCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id);
                } else {
                    $count = res_turn_calendar::where('start_date', $date)
                        ->where('res_turn_id', $res_turn_id)
                        ->count();

                    if ($count > 0) {
                        $this->deleteCalendarEquealStartDateCase($res_turn_id, $date);
                        $this->createCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id);
                    } else {
                        $count = res_turn_calendar::where('end_date', $date)
                            ->where('res_turn_id', $res_turn_id)
                            ->count();

                        if ($count > 0) {
                            $this->deleteCalendarEquealEndDateCase($res_turn_id, $date);
                            $this->createCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id);
                        } else {
                            $this->deleteCalendarBetweenDatesCase($res_turn_id, $date);
                            $this->createCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id);
                        }
                    }
                }
            });

            return true;
        }
    }

    private function createCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id)
    {
        $res_turn = res_turn::find($res_shift_id);

        $this->existConflictCalendarInDay($res_turn, $res_turn_id, $date, $microsite_id);

        $date_calendar = Carbon::createFromFormat('Y-m-d', $date)->toDateString();

        res_turn_calendar::create([
            "res_type_turn_id" => $res_turn->res_type_turn_id,
            "res_turn_id"      => $res_turn->id,
            "user_add"         => 1,
            "date_add"         => Carbon::now(),
            "start_date"       => $date_calendar,
            "end_date"         => $date_calendar,
            "start_time"       => $res_turn->hours_ini,
            "end_time"         => $res_turn->hours_end,
        ]);
    }

    private function updateCalendarHelper($res_turn_id, $res_shift_id, $date, $microsite_id)
    {
        $res_turn = res_turn::find($res_shift_id);

        $this->existConflictCalendarInDay($res_turn, $res_turn_id, $date, $microsite_id);

        res_turn_calendar::where('start_date', $date)
            ->where('end_date', $date)->where('res_turn_id', $res_turn_id)
            ->update([
                "res_type_turn_id" => $res_turn->res_type_turn_id,
                "res_turn_id"      => $res_turn->id,
                "user_upd"         => 2,
                "date_upd"         => Carbon::now(),
                "start_time"       => $res_turn->hours_ini,
                "end_time"         => $res_turn->hours_end,
            ]);
    }

    private function existConflictCalendarInDay(res_turn $res_turn, $res_turn_id, $date, $microsite_id)
    {
        $param                    = explode("-", $date);
        list($year, $month, $day) = $param;

        $data = $this->getList($microsite_id, $year, $month, $day);

        foreach ($data as $row) {
            if ($row["turn"]["id"] !== $res_turn_id) {
                DateTimesHelper::compareTimes(
                    $row["start_time"], $row["end_time"], $res_turn->hours_ini, $res_turn->hours_end, $date, true
                );
            }
        }
    }

    private function deleteCalendarEquealStartDateCase($res_turn_id, $date)
    {
        $dateUpdate = Carbon::createFromFormat('Y-m-d', $date)->addDays(7);
        DB::Table('res_turn_calendar')->where('start_date', $date)
            ->where('res_turn_id', $res_turn_id)->update([
            'start_date' => $dateUpdate,
        ]);
    }

    private function deleteCalendarEquealEndDateCase($res_turn_id, $date)
    {
        $dateUpdate = Carbon::createFromFormat('Y-m-d', $date)->addDays(-7);
        DB::Table('res_turn_calendar')->where('end_date', $date)
            ->where('res_turn_id', $res_turn_id)->update([
            'end_date' => $dateUpdate,
        ]);
    }

    private function deleteCalendarBetweenDatesCase($res_turn_id, $date)
    {
        $res_turn_calendar_aux = res_turn_calendar::where('res_turn_id', $res_turn_id)
            ->whereRaw('weekday(start_date) = weekday(\'' . $date . '\')')
            ->orderBy('start_date', 'desc')
            ->first();

        $dateUpdate = Carbon::createFromFormat('Y-m-d', $date)->addDays(-7);

        DB::Table('res_turn_calendar')
            ->where('start_date', $res_turn_calendar_aux->start_date)
            ->where('res_turn_id', $res_turn_calendar_aux->res_turn_id)
            ->update([
                'end_date' => $dateUpdate,
            ]);

        $res_turn_calendar                   = new res_turn_calendar();
        $res_turn_calendar->res_turn_id      = $res_turn_calendar_aux->res_turn_id;
        $res_turn_calendar->res_type_turn_id = $res_turn_calendar_aux->res_type_turn_id;
        $res_turn_calendar->start_date       = Carbon::createFromFormat('Y-m-d', $date)->addDays(7)->toDateString();
        $res_turn_calendar->end_date         = $res_turn_calendar_aux->end_date;
        $res_turn_calendar->start_time       = $res_turn_calendar_aux->start_time;
        $res_turn_calendar->end_time         = $res_turn_calendar_aux->end_time;
        $res_turn_calendar->date_add         = Carbon::now();
        $res_turn_calendar->user_add         = 1;
        $res_turn_calendar->save();
    }

    /**
     *  Retonar la zonas disponible para una fecha, consultando los turnos disponibles en en la fecha indicada
     * @param  Int    $microsite
     * @param  String    $date
     * @return  Illuminate\Database\Eloquent\Collection App\res_zone
     */
    public function getBlock(Int $microsite, String $date)
    {
        $now   = Carbon::now();
        $turns = res_turn_calendar::where(function ($query) use ($date) {
            $query->whereRaw("start_date = ? and end_date = ?", array($date, $date));
        })
            ->orWhere(function ($query) use ($date) {
                $query->whereRaw("start_date <= ?  and end_date >= ? and dayofweek(start_date) = dayofweek(?)", array($date, $date, $date));
            })
            ->distinct()->get()->pluck("res_turn_id");

        $zones = res_zone::join("res_turn_zone as tz", "tz.res_zone_id", "=", "res_zone.id")
            ->whereIn('tz.res_turn_id', $turns)
            ->where('ms_microsite_id', $microsite)
            ->distinct()
            ->with(['tables' => function($query) {
                return $query->with(["turns" => function($query) {
                    return $query->where("res_turn_rule_id", 0);
                }]);
            }])
            ->get(array(
                "id",
                "name",
                "sketch",
                "status",
                "type_zone",
                "join_table",
                "status_smoker",
                "people_standing",
            ));

        return $zones;
    }

}
