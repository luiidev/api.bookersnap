<?php

namespace App\Services;

/**
 * Servicio para gestionar la informacion de las zonas de un micrositio.
 * @author USER
 */
use App\res_table;
use App\res_zone;
use App\Services\ZoneTableService;
use Illuminate\Support\Facades\DB;

class ZoneService
{

    protected $_ZoneTableService;

    public function __construct(ZoneTableService $ZoneTableService)
    {
        $this->_ZoneTableService = $ZoneTableService;
    }

    /**
     * lista de todas las zonas de un micrositio.
     * @param   int     $microsite_id  Identificador del micrositio.
     * @param   string  $with  ['turns'] obtener los turnos que puede usar una zona.
     * @return  array   Lista de Estructura de zonas
     */
    public function getList(int $microsite_id, $with)
    {

        $rows = res_zone::where('ms_microsite_id', $microsite_id)->with('tables')->where("status", "<>", 2);
        if (isset($with)) {
            $split = explode('|', $with);
            $rows  = (in_array("turns", $split)) ? $rows->with('turns') : $rows;
            $rows  = (in_array("turns.type_turn", $split)) ? $rows->with('turns.typeTurn') : $rows;
        }
        return $rows->get();
    }

    /**
     * Obtener una zona de un micrositio.
     * @param   int     $microsite_id  Identificador del micrositio.
     * @param   int     $zone_id  Identificador de la zona.
     * @param   string  $with  ['turns'] obtener los turnos que puede usar una zona.
     * @return  array   Lista de Estructura de zonas
     */
    public function get(int $microsite_id, int $zone_id, $with)
    {

        $rows = res_zone::where('id', $zone_id)->where('ms_microsite_id', $microsite_id)->with('tables');
        if (isset($with)) {
            $split = explode('|', $with);
            $rows  = (in_array("turns", $split)) ? $rows->with('turns') : $rows;
            $rows  = (in_array("turns.type_turn", $split)) ? $rows->with('turns.typeTurn') : $rows;
        }
        return $rows->first();
    }

    /**
     * Obtener una zona de un micrositio.
     * @param   array   $data  Estructura de datos a registrar (zona y sus mesas).
     * @param   int     $microsite_id  Identificador del micrositio.
     * @param   int     $user_id  Identificador del usuario que va ha registrar la zona.
     * @return  array   Lista de Estructura de zonas
     */
    public function create(array $data, int $microsite_id, int $user_id)
    {
        try {
            $date_now              = \Carbon\Carbon::now();
            $zone                  = new res_zone();
            $zone->ms_microsite_id = $microsite_id;
            $zone->name            = $data['name'];
            $zone->sketch          = isset($data['sketch']) ? $data['sketch'] : null;
            $zone->type_zone       = isset($data['type_zone']) ? $data['type_zone'] : 0;
            $zone->join_table      = isset($data['join_table']) ? $data['join_table'] : 0;
            $zone->status_smoker   = isset($data['status_smoker']) ? $data['status_smoker'] : 0;
            $zone->people_standing = isset($data['people_standing']) ? $data['people_standing'] : 0;
            $zone->user_add        = $user_id;
            $zone->user_upd        = $user_id;
            $zone->date_add        = $date_now;
            $zone->date_upd        = $date_now;
            DB::BeginTransaction();
            $zone->save();
            foreach ($data['tables'] as $value) {
                $this->_ZoneTableService->create($value, $zone->id, $user_id);
            }
            DB::Commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno ");
        }
    }

    /**
     * Editar una zona de un micrositio.
     * @param   array   $data  Estructura de datos a editar (zona y sus mesas).
     * @param   int     $zone_id  Identificador de la zona.
     * @param   int     $user_id  Identificador del usuario que va ha editar la zona.
     * @return  boolean [true|false]
     */
    public function update(array $data, int $zone_id, int $user_id)
    {

        try {
            $date_now              = \Carbon\Carbon::now();
            $zone                  = res_zone::where('id', $zone_id)->first();
            $zone->name            = isset($data['name']) ? $data['name'] : $zone->name;
            $zone->sketch          = isset($data['sketch']) ? $data['sketch'] : $zone->sketch;
            $zone->type_zone       = isset($data['type_zone']) ? $data['type_zone'] : $zone->type_zone;
            $zone->join_table      = isset($data['join_table']) ? $data['join_table'] : $zone->join_table;
            $zone->status_smoker   = isset($data['status_smoker']) ? $data['status_smoker'] : $zone->status_smoker;
            $zone->people_standing = isset($data['people_standing']) ? $data['people_standing'] : $zone->people_standing;
            $zone->user_upd        = $user_id;
            $zone->date_upd        = $date_now;

            DB::BeginTransaction();
            $zone->save();
            foreach ($data['tables'] as $value) {
                if ($this->_ZoneTableService->exists($zone_id, @$value["id"])) {
                    $this->_ZoneTableService->update($value, $value["id"], $user_id);
                } else {
                    $this->_ZoneTableService->create($value, $zone_id, $user_id);
                }
            }
            DB::Commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
        return false;
    }

    /**
     * Eliminacion lógica de una zona de un micrositio.
     * @param   array   $microsite_id   Identificador del micrositio.
     * @param   int     $zone_id        Identificador de la zona.
     * @return  boolean [true|false]
     */
    public function delete(int $microsite_id, int $zone_id)
    {
        try {
            $zone = new res_zone();
            DB::BeginTransaction();
            $zone->where('id', $zone_id)->where('ms_microsite_id', $microsite_id)->update(["status" => 2]);
            DB::Commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function getListTable(int $microsite_id, int $zone_id)
    {
        $EnableTimesForTable = new \App\Domain\EnableTimesForTable();
        $disablet            = $EnableTimesForTable->disabled();
        $tables              = res_table::where('res_zone_id', $zone_id)->where('status', 1)->get(array('id', 'name', 'min_cover', 'max_cover'))->map(function ($item) use ($disablet) {
            $item->availability = $disablet;
            return $item;
        });
        return $tables;
    }

    /**
     * Obtener turno con sus dias asignados.
     * @param zone_id int   Identificador de la zona de un local.
     * @param id int        Identificador del turno de una zona.
     * @return array        Estructura turno
     */
    public function availableDaysForTypeturn(int $zone_id, int $type_turn_id)
    {
        try {
            $turnos = \App\res_turn_zone::where('res_type_turn_zone_id', $type_turn_id)->where('res_zone_id', $zone_id)->get()->map(function ($item, $key) {
                return $item->id;
            })->toArray();

            $dias = res_day_turn_zone::whereIn('res_turn_zone_id', $turnos)
                ->distinct()
                ->get();

            $turnDomain = new TurnDomain();
            return $turnDomain->availableDays($dias);
        } catch (\Exception $e) {
            return array();
        }
    }

}
