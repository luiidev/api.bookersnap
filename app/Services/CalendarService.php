<?php

namespace App\Services;

use App\Domain\Calendar;
use App\res_turn;
use App\res_turn_calendar;
use App\res_type_turn;
use App\res_zone;
use App\Services\Helpers\CalendarHelper;
use App\Services\Helpers\DateTimesHelper;
use Carbon\Carbon;
use DB;

class CalendarService
{

    public function listTurns(int $microsite_id, $start_date, $end_date = null)
    {
//        $turnsIdsCalendar = TurnsHelper::TurnIdsCalendar($microsite_id, $date);
        //        $turnsIdsEvens = TurnsHelper::IdsTurnEventFreeByDate($microsite_id, $date);

        $turnsIdsEvens    = res_turn::inEventFree($start_date, $end_date)->where('ms_microsite_id', $microsite_id)->pluck('id')->toArray();
        $turnsIdsCalendar = res_turn::inCalendar($start_date, $end_date)->where('ms_microsite_id', $microsite_id)->pluck('id')->toArray();

        $turnsIds = collect(array_merge($turnsIdsCalendar, $turnsIdsEvens))->unique();
        return res_turn::whereIn('id', $turnsIds->toArray())->with('typeTurn')->get();
    }

    public function getList(int $microsite_id, int $year, int $month, int $day = null)
    {
        if (is_null($day)) {
            $calendar  = new Calendar($year, $month);
            $date      = Carbon::parse("$year-$month-01");
            $startDate = $date->copy()->subDay(14);
            $endDate   = $date->copy()->lastOfMonth()->addDay(14);
            $calendar->setFixDate($startDate, $endDate);
//
            //return Helpers\CalendarHelper::searchDate($microsite_id);
            $turns = res_turn_calendar::fromMicrosite($microsite_id, $calendar->FIRST_DATE, $calendar->END_DATE);
        } else {
            $calendar = new Calendar($year, $month, $day);
            $date     = Carbon::parse($year . "-" . $month . "-" . $day);
            $turns    = res_turn_calendar::fromMicrosite($microsite_id, $date->toDateString());
        }
        $turns = $turns->with(["turn.zones"])->get()->map(function ($item) {
            return (object) [
                'title'      => $item->turn->name,
                // 'start_time' => $item->turn->hours_ini,
                // 'end_time'   => $item->turn->hours_end,
                'start_time' => $item->start_time,
                'end_time'   => $item->end_time,
                'color'      => $item->turn->typeTurn->color,
                'start_date' => $item->start_date,
                'end_date'   => $item->end_date,
                //                    'ms_microsite_id'      => $item->turn->ms_microsite_id,
                'turn'       => $item->turn->toArray(),
            ];
        });

        foreach ($turns as $turn) {
            $calendar->generateByWeekDay($turn, $turn->start_date, $turn->end_date);
        }
        return $calendar->get();
    }

    public function create(int $microsite_id, int $res_turn_id, string $date)
    {
        $this->createCalendarHelper(null, $res_turn_id, $date, $microsite_id);
    }

    /**
     * Lista de los turnos Turnos con su horarios en una fecha
     * @param int $microsite_id
     * @param string $date
     * @return type
     */
    public function getListShift(int $microsite_id, string $date)
    {

        return res_type_turn::where('status', 1)->with(['turns' => function ($query) use ($microsite_id, $date) {
            return $query->InCalendar($date)->where('ms_microsite_id', $microsite_id);
        }])->get()->map(function ($item) {
            $item->turn = (@$item->turns[0]) ? $item->turns[0] : null;
            unset($item->turns);
            return $item;
        });

    }

    public function deleteCalendar(int $res_turn_id, string $date)
    {

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
     *  Retonar la zonas y sus mesas activas en una fecha o rango de fecha
     * @param  Int    $microsite_id
     * @param  String    $date
     * @return  Illuminate\Database\Eloquent\Collection App\res_zone
     */
    public function getZones(Int $microsite_id, String $date, String $date_end = null)
    {
        $date     = CalendarHelper::realDate($microsite_id, $date);
        $date_end = (strcmp($date_end, $date) > 0) ? $date_end : $date;

        $turnIds          = res_turn_calendar::fromMicrosite($microsite_id, $date, $date_end)->orderBy('start_date')->pluck('res_turn_id')->toArray();
        $turnIdsEventfree = res_turn::inEventFreeActive($date, $date_end)->where('ms_microsite_id', $microsite_id)->pluck('id')->toArray();

        $turncollectIds = collect(array_merge($turnIds, $turnIdsEventfree));

        $zoneIds = \App\res_turn_zone::whereIn('res_turn_id', $turncollectIds)->groupBy('res_zone_id')->pluck('res_zone_id');

        return \App\res_zone::whereIn('id', $zoneIds)
            ->where('ms_microsite_id', $microsite_id)
            ->with(['tables' => function ($query) {
                return $query->with(["turns" => function ($query) {
                    return $query->where("res_turn_rule_id", 0)->orderBy("start_time", "asc");
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
    }

    private function turnsIdsByRangeDate(int $microsite_id, string $date_ini, string $date_end)
    {

        $fecha     = \Carbon\Carbon::parse($date_ini);
        $dayOfWeek = $fecha->dayOfWeek + 1;

        $fechaEnd     = \Carbon\Carbon::parse($date_end);
        $dayOfWeekEnd = $fecha->dayOfWeek + 1;
        /* Obtener Los Ids de los turnos Habilitados para un rabgo de fecha */
        return $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
            ->where("res_turn.ms_microsite_id", $microsite_id)
            ->where(function ($query) use ($fecha, $fechaEnd) {
                return $query->where("start_date", "<=", $fecha->toDateString())->where("end_date", ">=", $fecha->toDateString())
                    ->orWhere("start_date", ">=", $fecha->toDateString())->where("start_date", "<=", $fechaEnd->toDateString());
            })
            ->pluck('id');
    }

}
