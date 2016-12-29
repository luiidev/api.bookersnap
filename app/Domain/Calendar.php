<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Domain;

/**
 * Description of GenerateCalendar
 *
 * @author USER
 */
use Carbon\Carbon;

class Calendar
{

    public $NOW_DATETIME;
    public $FIRST_DATETIME;
    public $END_DATETIME;
    public $FIRST_DATE;
    public $END_DATE;
    public $NOW_DATE;
    private $DATA;

    /**
     * Generar fechas por dias de la semana en un rango definido.
     * @param   int $year Numero del anio.
     * @param   int $month Numero del mes.
     */
    public function __construct(int $year, int $month, int $day = null)
    {
        $this->NOW_DATETIME   = Carbon::create($year, $month, $day);
        $this->FIRST_DATETIME = $this->firstDayCalendar($year, $month);
        $this->END_DATETIME   = $this->endDayCalendar($year, $month)->addDays(7);
        $this->FIRST_DATE     = $this->FIRST_DATETIME->format('Y-m-d');
        $this->END_DATE       = $this->END_DATETIME->format('Y-m-d');
        $this->NOW_DATE       = $this->NOW_DATETIME->format('Y-m-d');
        $this->DATA           = [];
    }

    public function setFixDate(Carbon $dateIni, Carbon $dateFin)
    {

        // $this->NOW_DATETIME = Carbon::create($year, $month, $day);
        $this->FIRST_DATETIME = $dateIni;
        $this->END_DATETIME   = $dateFin;
        $this->FIRST_DATE     = $this->FIRST_DATETIME->format('Y-m-d');
        $this->END_DATE       = $this->END_DATETIME->format('Y-m-d');
        // $this->NOW_DATE       = $this->NOW_DATETIME->format('Y-m-d');
        // $this->DATA = [];

    }

/**
 * Generar DateTime del ultimo dia de un calendario.
 * @param   int $year Numero del anio.
 * @param   int $month Numero del mes.
 * @return  DateTime        ultimo dia del calendario de un mes.
 */
    protected function endDayCalendar(int $year, int $month)
    {
        $day = Carbon::create($year, $month);
        $day->endOfMonth();
        return $day->addDay(6 - $day->dayOfWeek);
    }

/**
 * Generar DateTime del primer dia de un calendario.
 * @param   int $year Numero del anio.
 * @param   int $month Numero del mes.
 * @return  DateTime        Primer dia del calendario de un mes.
 */
    protected function firstDayCalendar(int $year, int $month)
    {
        $day = Carbon::create($year, $month, 1);
        return $day->subDay($day->dayOfWeek);
    }

    protected function firstDateTime(string $date)
    {
        $startDatetime            = \Carbon\Carbon::parse($date);
        // $res                      = $this->FIRST_DATETIME->diff($startDatetime);
        $compare = strcmp($startDatetime->toDateTimeString(), $this->FIRST_DATETIME->toDateTimeString());
        // if ($res->invert == 1) {
        if ($compare < 0) {
//            $dayOfWeek = $startDatetime->dayOfWeek;
            // $startDatetime = \Carbon\Carbon::create($this->FIRST_DATETIME->year, $this->FIRST_DATETIME->month, $this->FIRST_DATETIME->day)->addDay($dayOfWeek);
            if($this->FIRST_DATETIME->dayOfWeek < $startDatetime->dayOfWeek){
                $difday = $startDatetime->dayOfWeek - $this->FIRST_DATETIME->dayOfWeek;
                $startDatetime = $this->FIRST_DATETIME->copy()->addDay($difday);
            }else{
                $difday = $this->FIRST_DATETIME->dayOfWeek - $startDatetime->dayOfWeek;
                $startDatetime = $this->FIRST_DATETIME->copy()->addDay(7 - $difday);
            }
            
        }
        return $startDatetime;
    }

    protected function endDateTime(string $date = null)
    {
        if ($date == null) {
            return $this->END_DATETIME;
        }
        list($year, $month, $day) = explode('-', $date);
        $endDatetime              = \Carbon\Carbon::create($year, $month, $day);
        $res                      = $this->END_DATETIME->diff($endDatetime);
        $compare                  = strcmp($this->END_DATETIME->toDateTimeString(), $endDatetime->toDateTimeString());
        // if ($res->invert == 0) {
        if ($compare <= 0) {
            $endDatetime = $this->END_DATETIME;
        }
        return $endDatetime;
    }

/**
 * Generar fechas por dias de la semana en un rango definido.
 * @param   string $start_date Fecha de inicio de las fechas a generar.
 * @param   string $end_date Fecha de termino de las fechas a generar.
 * @param   string $turn Objeto al que se asignara una fecha.
 * @param   function $callback Redefinir objeto.
 */
    public function generateByWeekDay($turn, string $start_date, string $end_date = null)
    {
        $startDatetime = $this->firstDateTime($start_date);
        $endDatetime   = $this->endDateTime($end_date);
        $compare       = strcmp($startDatetime->toDateTimeString(), $endDatetime->toDateTimeString());
        // $interval = $startDatetime->diff($endDatetime);
        // if ($interval->invert == 0) {
        if ($compare <= 0) {
            $turn_array         = is_object($turn) ? (array) $turn : $turn;
            $turn_array['date'] = $startDatetime->format('Y-m-d');
            $this->DATA[]       = $turn_array;
            $startDatetime->addDay(7);
            $this->generateByWeekDay($turn, $startDatetime->format('Y-m-d'), $endDatetime->format('Y-m-d'));
        }
    }

/**
 * Obtener todos los objetos con sus fechas asignadas.
 * @return array Lista de objetos con sus fechas asignadas.
 */
    public function get()
    {
        $result     = $this->DATA;
        $this->DATA = null;
        return $result;
    }

    public function shiftByDay()
    {
        $data   = collect($this->get());
        $result = $data->where('date', $this->NOW_DATE)->all();
        return array_values($result);
    }

}
