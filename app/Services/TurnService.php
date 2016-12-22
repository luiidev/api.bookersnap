<?php

namespace App\Services;

use App\Http\Requests\TurnRequest;
use App\res_table;
use App\res_turn;
use App\res_turn_table;
use App\res_turn_zone;
use App\Services\Helpers\CreateTurnCalendarHelper;
use App\Services\Helpers\CreateTurnHelper;
use App\Services\Helpers\TurnServiceHelper;
use Carbon\Carbon;
use DB;
use App\Services\Helpers\TurnsHelper;

class TurnService {

    /**
     * Colleccion de todos los turnos activos
     * @param int $microsite_id
     * @param string $with
     * @return type
     */
    public function actives(int $microsite_id, string $with = null) {
        
        $turnIdscalendar = TurnsHelper::IdsCalendarActives($microsite_id);
        
        $rows = res_turn::with('typeTurn')->where('ms_microsite_id', $microsite_id)->whereIn('id', $turnIdscalendar);
        if (!empty($params)) {
            if (!empty($params['hours_ini'])) {
                $rows = $rows->where('hours_ini', $params['hours_ini']);
            }

            if (!empty($params['hours_end'])) {
                $rows = $rows->where('hours_end', $params['hours_end']);
            }
            if (!empty($params['type_turn'])) {
                $rows = $rows->where('res_type_turn_id', $params['type_turn']);
            }
        }
        $rows = $rows->get();

        return $rows;
    }
    
    public function search(int $microsite_id, array $params) {
        
        $rows = res_turn::with('typeTurn')->where('ms_microsite_id', $microsite_id);
        if (!empty($params)) {
            if (!empty($params['hours_ini'])) {
                $rows = $rows->where('hours_ini', $params['hours_ini']);
            }

            if (!empty($params['hours_end'])) {
                $rows = $rows->where('hours_end', $params['hours_end']);
            }
            if (!empty($params['type_turn'])) {
                $rows = $rows->where('res_type_turn_id', $params['type_turn']);
            }
        }
        $rows = $rows->get();

        return $rows;
    }

    public function getList(int $microsite_id, string $with = null, string $type_turn = null) {

        $rows = res_turn::where('ms_microsite_id', $microsite_id);

        $rows = isset($type_turn) ? $rows->whereIn('res_type_turn_id', explode(",", $type_turn)) : $rows;

        if (isset($with)) {
            $data = explode('|', $with);
            $rows = (in_array("type_turn", $data)) ? $rows->with('typeTurn') : $rows;
            $rows = (in_array("turn_time", $data)) ? $rows->with('turnTime') : $rows;
            $rows = (in_array("turn_zone", $data)) ? $rows->with('turnZone') : $rows;
            $rows = (in_array("turn_zone.zone", $data)) ? $rows->with('turnZone.zone') : $rows;
            $rows = (in_array("turn_zone.zone.turns", $data)) ? $rows->with('turnZone.zone.turns') : $rows;
            $rows = (in_array("turn_zone.zone.tables", $data)) ? $rows->with('turnZone.zone.tables') : $rows;
            $rows = (in_array("turn_zone.rule", $data)) ? $rows->with('turnZone.rule') : $rows;
            $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
            $rows = (in_array("zones.tables", $data)) ? $rows->with('zones.tables') : $rows;
            $rows = (in_array("zones.turns", $data)) ? $rows->with('zones.turns') : $rows;
            $rows = (in_array("calendar", $data)) ? $rows->with('calendar') : $rows;
        }
        return $rows->get();
    }

    public function calendar(int $microsite_id, string $with = null, string $type_turn = null, string $date = null) {

        if (is_null($date)) {
            $date = Helpers\CalendarHelper::realDate($microsite_id);
        }
        $fecha = Carbon::parse($date);
        $nextDay = $fecha->copy()->addDay();
        $dayOfWeek = $fecha->dayOfWeek + 1;

        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                ->where("res_turn.ms_microsite_id", $microsite_id)
                ->where("start_date", "<=", $fecha->toDateString())
                ->where("end_date", ">=", $fecha->toDateString())
                ->pluck('id');

        $rows = res_turn::inCalendar($date)->select(array(
                    'id',
                    'name',
                    'res_type_turn_id',
                    'hours_ini',
                    'hours_end',
//                    DB::raw("'" . $fecha->toDateString() . "' AS date_ini"),
//                    DB::raw("IF(hours_end > hours_ini, '" . $fecha->toDateString() . "', '" . $nextDay->toDateString() . "') AS date_end"),
//                    DB::raw("CONCAT('" . $fecha->toDateString() . "',' ',hours_ini) AS start_datetime"),
//                    DB::raw("IF(hours_end > hours_ini, CONCAT('" . $fecha->toDateString() . "',' ',hours_end), CONCAT('" . $nextDay->toDateString() . "',' ',hours_end)) AS end_datetime")
                ))->where('ms_microsite_id', $microsite_id);
        
        $rows = isset($type_turn) ? $rows->whereIn('res_type_turn_id', explode(",", $type_turn)) : $rows;

        if (isset($with)) {
            $data = explode('|', $with);
            $rows = (in_array("type_turn", $data)) ? $rows->with('typeTurn') : $rows;
            $rows = (in_array("turn_time", $data)) ? $rows->with('turnTime') : $rows;
            $rows = (in_array("turn_zone", $data)) ? $rows->with('turnZone') : $rows;
            $rows = (in_array("turn_zone.zone", $data)) ? $rows->with('turnZone.zone') : $rows;
            $rows = (in_array("turn_zone.zone.turns", $data)) ? $rows->with('turnZone.zone.turns') : $rows;
            $rows = (in_array("turn_zone.zone.tables", $data)) ? $rows->with('turnZone.zone.tables') : $rows;
            $rows = (in_array("turn_zone.rule", $data)) ? $rows->with('turnZone.rule') : $rows;
            $rows = (in_array("zones", $data)) ? $rows->with('zones') : $rows;
            $rows = (in_array("zones.tables", $data)) ? $rows->with('zones.tables') : $rows;
            $rows = (in_array("zones.turns", $data)) ? $rows->with('zones.turns') : $rows;
            $rows = (in_array("calendar", $data)) ? $rows->with('calendar') : $rows;
        }

        return $rows->get();
    }

    public function get(int $microsite_id, int $turn_id, $with) {
        try {
            $query = res_turn::where('id', $turn_id)->where('ms_microsite_id', $microsite_id)->with("weekDays");

            if (isset($with)) {
                $data = explode('|', $with);
                if (in_array("type_turn", $data)) {
                    $query->with('typeTurn');
                }

                if (in_array("turn_time", $data)) {
                    $query->with('turnTime');
                }

                if (in_array("turn_zone", $data)) {
                    $query->with('turnZone');
                }

                if (in_array("turn_zone.zone", $data)) {
                    $query->with('turnZone.zone');
                }

                if (in_array("turn_zone.zone.turns", $data)) {
                    $query->with('turnZone.zone.turns');
                }

                if (in_array("turn_zone.zone.tables", $data)) {
                    $query->with('turnZone.zone.tables');
                }

                if (in_array("turn_zone.rule", $data)) {
                    $query->with('turnZone.rule');
                }

                if (in_array("zones", $data)) {
                    $query->with('zones');
                }

                if (in_array("zones.tables", $data)) {
                    $query->with('zones.tables');
                }

                if (in_array("zones.turns", $data)) {
                    $query->with('zones.turns');
                }

                $query = (in_array("calendar", $data)) ? $query->with('calendar') : $query;
            }
            $turn = $query->first();
            if ($turn == null) {
                abort(500, "Ocurrio un error");
            }

            $turn->days = $turn->weekDays;

            return $turn;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function create(TurnRequest $request, int $microsite_id, int $user_id) {
        try {

            if ($request->has("days")) {

                $days = request("days");
                $type_turn_id = request("res_type_turn_id");

                $calendar = CreateTurnHelper::make($days, $type_turn_id, (int) $microsite_id);
                $calendar->generate(request("hours_ini"), request("hours_end"));

                if ($calendar->fails()) {
                    $conflicts = $calendar->getConflict();
                    foreach ($conflicts as $key => $value) {
                        $value->setVisible(["name", "start_date", "end_date", "start_time", "end_time"]);
                    }

                    return ["response" => "fail", "data" => $conflicts];
                }
            }

            DB::beginTransaction();

            $turn = $this->createTurnService($request, $microsite_id, $user_id);

            if ($request->has("days")) {
                foreach ($days as $day) {
                    $periodic = $calendar->existsPeriodic($day);
                    $uniques = $calendar->existsUniques($day);

                    $date = CreateTurnHelper::nextDayWeek($day);

                    if ($periodic) {
                        // Reemplazar el  turno del calendario periodico por nuevo turno creado
                        $old_turn = $calendar->getPeriodic($day);
                        $pieces = $calendar->getUniqueDays($day);
                        CreateTurnCalendarHelper::calendarPeriodicCase($turn, $old_turn, $pieces, $date, $user_id);
                    } else if ($uniques) {
                        // Crear un nuevo calendario periodico con el nuevo turno, creando cortes en los dias unicos del tipo de turno a crear
                        $pieces = $calendar->getUniqueDays($day);
                        CreateTurnCalendarHelper::calendarPiecesOnlyCase($turn, $pieces, $date, $user_id);
                    } else {
                        // Crear un nuevo calendario periodico con el nuevo turno
                        CreateTurnCalendarHelper::calendarFreeCase($turn, $date, $user_id);
                    }
                }
            }

            DB::commit();

            return ["response" => "ok"];
        } catch (\Exception $e) {
            DB::rollBack();
            abort(423, "Ocurrio un error al intentar crear el turno junto al calendario");
        }
    }

    private function createTurnService(TurnRequest $request, int $microsite_id, int $user_id) {
        try {
            $turn = new res_turn();
            $turn->name = $request->name;
            $turn->ms_microsite_id = $microsite_id;
            $turn->res_type_turn_id = $request->res_type_turn_id;
            $turn->hours_ini = $request->hours_ini;
            $turn->hours_end = $request->hours_end;
            $turn->user_add = $user_id;
            $turn->user_upd = $user_id;
            $turn->date_add = Carbon::now();
            $turn->date_upd = $turn->date_add;

            DB::BeginTransaction();
            $turn->save();
            unset($turn_zones);
            $turn_zones = array();
            foreach ($request->turn_zone as $value) {
                $this->saveTurnTables(@$value["tables"], $turn->hours_ini, $turn->hours_end, $turn->id);
                $turn_zones[$value['res_zone_id']] = array('res_turn_rule_id' => $value['res_turn_rule_id']);
            }
            $turn->zones()->attach($turn_zones);

            $turn_times = [];
            DB::table('res_turn_time')->where('res_turn_id', $turn->id)->delete();
            foreach ($request->turn_time as $value) {
                array_push($turn_times, array('res_turn_id' => $turn->id, 'num_guests' => $value['num_guests'], 'time' => $value['time']));
            }
            DB::table('res_turn_time')->insert($turn_times);



            DB::Commit();

            return $turn;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error al intentar crear el turno junto al calendario");
        }
    }

    public function update(TurnRequest $request, int $microsite_id, int $user_id) {

        try {

            $days_of_week = array(1, 2, 3, 4, 5, 6, 7);

            $type_turn_id = request("res_type_turn_id");

            if ($request->has("days")) {
                $days = request("days");

                //  Validar que exista conflicto entre los dias ingresados
                $calendar = CreateTurnHelper::make($days, $type_turn_id, (int) $microsite_id);
                $calendar->generate(request("hours_ini"), request("hours_end"));

                if ($calendar->fails()) {
                    $conflicts = $calendar->getConflict();
                    foreach ($conflicts as $key => $value) {
                        $value->setVisible(["name", "start_date", "end_date", "start_time", "end_time"]);
                    }

                    return ["response" => "fail", "data" => $conflicts];
                }
            } else {
                $days = array();
            }

            // Si paso la validacion, traer el calendario del tipo de turno
            $aux_calendar = CreateTurnHelper::make($days_of_week, $type_turn_id, (int) $microsite_id);

            DB::beginTransaction();

            // Editar turno
            $turn = $this->update_turn($request, $microsite_id, $user_id);

            foreach ($days_of_week as $day) {

                $periodic = $aux_calendar->existsPeriodic($day);
                $uniques = $aux_calendar->existsUniques($day);

                $date = CreateTurnHelper::nextDayWeek($day);

                if (in_array($day, $days)) {

                    if ($periodic) {
                        // Reemplazar el  turno del calendario periodico por nuevo turno creado
                        $old_turn = $aux_calendar->getPeriodic($day);
                        $pieces = $aux_calendar->getUniqueDays($day);
                        CreateTurnCalendarHelper::calendarPeriodicCase($turn, $old_turn, $pieces, $date, $user_id);
                    } else if ($uniques) {
                        // Crear un nuevo calendario periodico con el nuevo turno, creando cortes en los dias unicos del tipo de turno a crear
                        $pieces = $aux_calendar->getUniqueDays($day);
                        CreateTurnCalendarHelper::calendarPiecesOnlyCase($turn, $pieces, $date, $user_id);
                    } else {
                        // Crear un nuevo calendario periodico con el nuevo turno
                        CreateTurnCalendarHelper::calendarFreeCase($turn, $date, $user_id);
                    }
                } else {
                    // Eliminar calendario
                    if ($periodic) {
                        // Eliminar el  turno del calendario periodico por nuevo turno creado
                        $old_turn = $aux_calendar->getPeriodic($day);
                        $pieces = $aux_calendar->getUniqueDays($day);
                        CreateTurnCalendarHelper::calendarPeriodicCaseDelete($turn, $old_turn, $pieces, $date, $user_id);
                    }
                }
            }

            DB::commit();

            return ["response" => "ok"];
        } catch (\Exception $e) {
            DB::rollBack();
            abort(422, $e->getMessage());
            // abort(422, "Ocurrio un error al intentar editar el turno junto al calendario");
        } catch (\FatalThrowableError $e) {
            DB::rollBack();
            abort(422, $e->getMessage());
            // abort(422, "Ocurrio un error al intentar editar el turno junto al calendario");
        }
    }

    private function update_turn(TurnRequest $request, int $microsite_id, int $user_id) {
        $turn = res_turn::where('id', $request->route("turn_id"))
                ->where('ms_microsite_id', $microsite_id)
                ->first();

        $turn->name = request("name");
        $turn->res_type_turn_id = request("res_type_turn_id");
        $turn->hours_ini = request("hours_ini");
        $turn->hours_end = request("hours_end");
        $turn->user_upd = $user_id;
        $turn->date_upd = \Carbon\Carbon::now();

        $turn->save();

//        $turn_zones = [];
        foreach (request("turn_zone") as $value) {
            $this->update__saveTurnZone($value, $turn);
//            $turn_zones[$value['res_zone_id']] = array('res_turn_rule_id' => $value['res_turn_rule_id']);
        }
//        $turn->zones()->sync($turn_zones);
        $turn_times = [];
        DB::table('res_turn_time')->where('res_turn_id', $turn->id)->delete();
        foreach ($request->turn_time as $value) {
            array_push($turn_times, array('res_turn_id' => $turn->id, 'num_guests' => $value['num_guests'], 'time' => $value['time']));
        }
        DB::table('res_turn_time')->insert($turn_times);

        return $turn;
    }

    private function update__saveTurnZone(array $value, $turn) {
        if (!(@$value['unlink'] === true)) {
            if (res_turn_zone::where('res_turn_id', $turn->id)->where('res_zone_id', $value['res_zone_id'])->count() > 0) {
                $turn->zones()->updateExistingPivot($value['res_zone_id'], ['res_turn_rule_id' => $value['res_turn_rule_id']]);
            } else {
                $turn->zones()->attach($value['res_zone_id'], ['res_turn_rule_id' => $value['res_turn_rule_id']]);
            }
            $this->saveTurnTables(@$value["tables"], $turn->hours_ini, $turn->hours_end, $turn->id);
        } else {
            $arrayIdsTables = res_table::where('res_zone_id', $value['res_zone_id'])->get()->pluck(['id']);
            res_turn_table::whereIn('res_table_id', $arrayIdsTables)->where('res_turn_id', $turn->id)->delete();
            $turn->zones()->detach($value['res_zone_id']);
        }
    }

    public function unlinkZone(int $microsite_id, int $turn_id, int $zone_id) {
        try {
            if (res_turn::where('ms_microsite_id', $microsite_id)->where('id', $turn_id)->get()->count() > 0) {
                DB::BeginTransaction();
                $arrayIdsTables = res_table::where('res_zone_id', $zone_id)->get()->pluck(['id']);
                DB::table('res_turn_table')->whereIn('res_table_id', $arrayIdsTables)->where('res_turn_id', $turn_id)->delete();
                $turn = res_turn::findOrFail($turn_id);
                $turn->zones()->detach($zone_id);
                DB::Commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function formListTable(int $microsite, int $zone_id) {

        $turn = res_turn::where('id', $turn_id)->first();
        if ($turn != null) {
            $EnableTimesForTable = new \App\Domain\EnableTimesForTable();

            $tables = res_table::where('res_zone_id', $zone_id)->where('status', 1)->with(array('turns' => function ($query) use ($turn_id, $zone_id) {
                            $query->where('res_turn_id', $turn_id)->where('res_zone_id', $zone_id);
                        }))->get(array('id', 'name', 'min_cover', 'max_cover'))->map(function ($item) use ($turn, $EnableTimesForTable) {
                $item->availability = $EnableTimesForTable->segment($turn, $item->turns);
                unset($item->turns);
                return $item;
            });
            return $tables;
        }
        return $turn;
    }

    public function getListTable(int $turn_id, int $zone_id) {

        $turn = res_turn::whereHas('turnZone',function($query) use ($turn_id){
            return $query->where('id', $turn_id);
        })->first();

        if ($turn != null) {
            $EnableTimesForTable = new \App\Domain\EnableTimesForTable();

            $tables = res_table::where('res_zone_id', $zone_id)->where('status', 1)->with(array('turns' => function ($query) use ($turn_id) {
                            $query->where('res_turn_id', $turn_id);
                        }))->get(array('id', 'name', 'min_cover', 'max_cover'))->map(function ($item) use ($turn, $EnableTimesForTable) {
                $item->availability = $EnableTimesForTable->segment($turn, $item->turns);
                unset($item->turns);
                return $item;
            });
            return $tables;
        }
        return $turn;
    }

    private function saveTurnTables(array $tables = null, $hours_ini, $hours_end, int $turn_id) {

        if (is_array($tables)) {
            $TurnServiceHelper = new TurnServiceHelper();
            foreach ($tables as $table) {
                $table_id = $table["id"];
                $table_availability = $table["availability"];

                res_turn_table::where('res_turn_id', $turn_id)->where('res_table_id', $table_id)->delete();

                $turnTables = $TurnServiceHelper->createTurnTable($table_availability, $hours_ini, $hours_end, $turn_id, $table_id);
                foreach ($turnTables as $key => $turnTable) {
                    $entity = new res_turn_table();
                    $entity->res_table_id = $turnTable['res_table_id'];
                    $entity->res_turn_id = $turnTable['res_turn_id'];
                    $entity->start_time = $turnTable['start_time'];
                    $entity->end_time = $turnTable['end_time'];
                    $entity->next_day = $turnTable['next_day'];
                    $entity->res_turn_rule_id = $turnTable['res_turn_rule_id'];
                    $entity->save();
                }
            }
        }
        return true;
    }

    public function deleteTurn($microsite_id, $idTurn) {
        try {
            DB::BeginTransaction();
            $turn = res_turn::where('ms_microsite_id', $microsite_id)->where('id', $idTurn);
            $turn->delete();
            DB::Commit();
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

}
