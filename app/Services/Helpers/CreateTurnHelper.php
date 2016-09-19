<?php

namespace App\Services\Helpers;

use App\Services\Helpers\DateTimesHelper;
use App\res_turn_calendar;
use Carbon\Carbon;
use DB;


/**
 * Pendiente codigo de micrositio
 */


/**
* Helpers para manejo de conflicto de turnos y horaios al crear un nuevo turno  con calendario periodico
*/
class CreateTurnHelper
{

    public $fails;
    public $conflicts;
    public $conflict_calendar;
    public $periodic;
    public $unique_days;
    public $turn_conflct;
    public $now;


    public function __construct($days, $type_turn_id)
    {
        $this->fails = false;
        $this->turn_conflct = array();
        $this->now = Carbon::now()->toDateString();

        $this->CalendarConflict($days, $type_turn_id);
    }

    /**
     * Genera los turnos con los que podria generar conflicto el en calendario el turno a crear
     * @param array $days         lista de dias a filtrar
     * @param int $type_turn_id tipo de turno a evaluar, para agregar excepciones
     */
    private function CalendarConflict($days, $type_turn_id)
    {

        $this->periodic = res_turn_calendar::select(array("res_turn_id", "res_type_turn_id", "start_date", "end_date", DB::raw("dayofweek(start_date) as dayOfWeek")))
                                            ->whereIn(DB::raw("dayofweek(start_date)"), $days)
                                            ->where("res_type_turn_id", $type_turn_id)
                                            ->where("end_date", "9999-12-31")
                                            ->get();

        $unique_days_query = res_turn_calendar::select(array("res_turn_id", "start_date", "end_date", DB::raw("dayofweek(start_date) as dayOfWeek")))
                                            ->whereIn(DB::raw("dayofweek(start_date)"), $days)
                                            ->where("res_type_turn_id", $type_turn_id)
                                            ->whereColumn("start_date", "end_date")
                                            ->where("end_date", ">=", $this->now);

        // if ($this->periodic != null) {
        //     $unique_days_query->whereNotIn("res_turn_id", $this->periodic->pluck("res_turn_id"));
        // }

        $this->unique_days = $unique_days_query->get();

        $this->conflict_calendar = res_turn_calendar::whereIn(DB::raw("dayofweek(start_date)"), $days)
                                            ->where("res_type_turn_id", "<>", $type_turn_id)
                                            ->where("end_date", ">=", $this->now)
                                            ->get();

        $this->removeException();
    }

    /**
     * Remueve turnos que no se deben considerar
     * @return void
     */
    private function removeException()
    {
        foreach ($this->unique_days as $date) {
            foreach ($this->conflict_calendar as $i  => $row) {
                if ($row->start_date == $date->start_date && $row->end_date == $date->end_date) {
                    $this->conflict_calendar->splice($i, 1);
                    return $this->removeException();
                }
            }
        }
    }

    /**
     * Inicio del helper
     * @param array $days         lista de dias a filtrar
     * @param int $type_turn_id tipo de turno a evaluar, para agregar excepciones
     * @return App\Services\Helpers\CreateTurnHelper
     */
    public static function make(array $days,  int $type_turn_id)
    {
        return new static($days, $type_turn_id);
    }

    /**
     * Retorna fecha mas cercano al dia(s) ingresados
     * @param  array  $days [1,2,3,4,5,6]
     * @return Array      Dates
     */
    public static function nextDayWeek($days)
    {
        $dates;
        $now = Carbon::now()->setTime(0, 0, 0);

        if (is_array($days)) {

            $dates = array();
            foreach ($days as $dayOfWeek) {
                if ($now->dayOfWeek  == ($dayOfWeek - 1)) {
                    $dates[] = $now->copy();
                } else {
                    $dates[] = $now->copy()->next($dayOfWeek - 1);
                }
            }

        } else {

            if ($now->dayOfWeek  == ($days - 1)) {
                $dates = $now->copy();
            } else {
                $dates =  $now->copy()->next($days - 1);
            }

        }

        return $dates;
    }

    /**
     * Filtra los turnos con los que hay conflicto en el ranngo de horas que se a brindado
     * @param  String|Time $start_time Hora de inicio
     * @param  String|Time $end_time   Hora final
     * @return Void
     */
    public function generate($start_time, $end_time)
    {
         $this->turn_conflct = array();
         foreach ($this->conflict_calendar as $calendar) {
                 $validate = DateTimesHelper::
                                                        compareTimes(    
                                                             $calendar->start_time,
                                                             $calendar->end_time,
                                                             $start_time,
                                                             $end_time,
                                                             $this->now
                                                         );
                 if ($validate->fail){
                     $calendar->turn;
                     $this->turn_conflct[] = $calendar;
                 }
         }

         if (count($this->turn_conflct)) {
             $this->fails = true;
         }
    }

    /**
     * Pregunta si existe conflicto entre fechas
     * @return Boolean
     */
    public function fails()
    {
        return $this->fails;
    }

    /**
     * Retorna turnos con los que se genera conflictos
     * @return Array App\res_turn_calendar
     */
    public function getConflict()
    {
        return $this->turn_conflct;
    }

    /**
     * Pregunta si existe un calendario periodico en un dia de semana
     * @param  Int    $day día de semana
     * @return Boolean
     */
    public function existsPeriodic(int $day)
    {
        return count($this->periodic->where("dayOfWeek", $day)) > 0;
    }

    /**
     * Pregunta si existe dias unicos ene el calendario calendario un dia de semana
     * @param  Int    $day día de semana
     * @return Boolean
     */
    public function existsUniques(int $day)
    {
        return count($this->unique_days->where("dayOfWeek", $day)) > 0;
    }

    /**
     * Retorna los dias unicos encontrados en un dia de semana
     * @param  Int    $day día de semana
     * @return App\res_turn_calendar
     */
    public function getUniqueDays(int $day)
    {
        return $this->unique_days->where("dayOfWeek", $day)->orderBy("start_date", "asc");
    }

    /**
     * Retorna el turno periodico encontrado en un dia de semana
     * @param  Int    $day día de semana
     * @return App\res_turn_calendar
     */
    public function getPeriodic(int $day)
    {
        return $this->periodic->where("dayOfWeek", $day)->first();
    }
}