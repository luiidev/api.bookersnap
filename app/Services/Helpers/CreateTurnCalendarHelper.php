<?php

namespace App\Services\Helpers;

use App\res_turn;
use App\res_turn_calendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

    /**
    * Servicio de ayuda para creacion de calendario desde la creacion de un turno
    */
    class CreateTurnCalendarHelper
    {
        
        function __construct()
        {
            # code...
        }

        public static function calendarFreeCase(res_turn $res_turn,  Carbon $date)
        {
            $date = $date->toDateString();

            res_turn_calendar::create([
                            "res_type_turn_id"   =>  $res_turn->res_type_turn_id,
                            "res_turn_id"            =>  $res_turn->id,
                            "user_add"               =>  1,
                            "date_add"               =>  Carbon::now(),
                            "date_upd"               =>  Carbon::now(),
                            "start_date"              =>  $date,
                            "end_date"               =>  "9999-12-31",
                            "start_time"              =>  $res_turn->hours_ini,
                            "end_time"               =>  $res_turn->hours_end,
                        ]);
        }

        public static function calendarPeriodicCase(res_turn $res_turn, res_turn_calendar $old_turn, Collection $pieces, Carbon $date)
        {
            $now = Carbon::now();

            $periodic = res_turn_calendar::where("res_turn_id", $old_turn->res_turn_id)
                                            ->where("end_date", ">=", $now->toDateString())
                                            ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($old_turn->start_date))
                                            ->orderBy("end_date", "asc")
                                            ->get();

            $first_periodic = $periodic->first();

            $start_date_first_periodic = self::date($first_periodic->start_date);

            if ($periodic->count() > 1) {
                // Hay un periodico con fechas desperdiagadas

                if (  $start_date_first_periodic->lt( $date )) {
                    self::calendarPeriodicCaseCut($first_periodic, $date);
                    self::calendarPeriodicCaseInPieces($res_turn, $old_turn,$pieces, $date, $first_periodic);
                } else {
                    self::calendarPeriodicCaseOnlyReplace($res_turn, $old_turn);
                }

            } else if ($periodic->count() ==1 ){
                // Solo hay un una unica fecha periodica

                if (  $start_date_first_periodic->lt( $date )) {
                    self::calendarPeriodicCaseCut($first_periodic, $date);
                    self::calendarPeriodicCaseOnly($res_turn, $old_turn,$pieces, $date);    
                } else {
                    self::calendarPeriodicCaseOnlyReplace($res_turn, $old_turn);    
                }

            }

        }

        public static function calendarPiecesOnlyCase(res_turn $res_turn, Collection $pieces, Carbon $date)
        {

            $now = Carbon::now();

            $start_date =  $date;

            $last_key = self::last_key($pieces);

            $calendarArray = array();

            foreach ($pieces as $i => $calendar) {

                if ( $start_date->toDateString()  ==  $calendar->start_date) {
                    // Caso en que la fecha de inicio exista una pieza | dia unico

                    $start_date = Carbon::parse( $calendar->start_date )->addDays(7);
                    
                    if ( $i == $last_key) {
                        $end_date = Carbon::parse("9999-12-31");

                        $calendarArray = self::piecesCalendarArray($calendarArray, $res_turn, $start_date, $end_date);
                    }
                } else {

                    if ( $i == $last_key) {
                        $end_date = Carbon::parse( $pieces[ $i ]->start_date )->addDays(-7);
                        
                        $calendarArray = self::piecesCalendarArray($calendarArray, $res_turn, $start_date, $end_date);

                        $start_date = $end_date->addDays(14);
                        $end_date = Carbon::parse("9999-12-31");

                        $calendarArray = self::piecesCalendarArray($calendarArray, $res_turn, $start_date, $end_date);
                    } else {
                        $end_date = Carbon::parse( $pieces[ $i ]->start_date )->addDays(-7);
                        
                        $calendarArray = self::piecesCalendarArray($calendarArray, $res_turn, $start_date, $end_date);

                        $start_date = $end_date->addDays(14);
                    }
                }

            }

            res_turn_calendar::insert($calendarArray);
        }

        private static function last_key($pieces)
        {
            $last_key;
            foreach ($pieces as $key => $value) {
                $last_key = $key;
            }
            return $last_key;
        }

        private static function piecesCalendarArray(array $calendarArray, res_turn $res_turn, Carbon $start_date,  Carbon $date_end)
        {
            $now = Carbon::now();

            $calendar_piece =array(
                "res_turn_id"             => $res_turn->id,
                "res_type_turn_id"    => $res_turn->res_type_turn_id,
                "start_date"               => $start_date->toDateString(),
                "end_date"                => $date_end->toDateString(),
                "start_time"               => $res_turn->hours_ini,
                "end_time"                => $res_turn->hours_end,
                "date_add"               => $now->toDateString(),
                "date_upd"               => $now->toDateString(),
                "user_add"               => 1
            );

            array_push($calendarArray, $calendar_piece);

            return $calendarArray;
        }

        private static function calendarPeriodicCaseCut(res_turn_calendar $turn_periodic, Carbon $date)
        {
            $date_update =$date->copy()->addDays(-7);

            res_turn_calendar::where('start_date', $turn_periodic->start_date)
                        ->where('res_turn_id', $turn_periodic->res_turn_id)
                        ->update([
                                'end_date' => $date_update
                        ]);
        }

        private static function calendarPeriodicCaseInPieces(res_turn $res_turn, res_turn_calendar $old_turn, Collection $pieces, Carbon $date, res_turn_calendar $turn_periodic)
        {
            $now = Carbon::now();

            $res_turn_calendar = new res_turn_calendar();
            $res_turn_calendar->res_turn_id             = $turn_periodic->res_turn_id;
            $res_turn_calendar->res_type_turn_id    = $turn_periodic->res_type_turn_id;
            $res_turn_calendar->start_date               = $date->toDateString();
            $res_turn_calendar->end_date                = $turn_periodic->end_date;
            $res_turn_calendar->start_time               = $turn_periodic->start_time;
            $res_turn_calendar->end_time                = $turn_periodic->end_time;
            $res_turn_calendar->date_add               = $now->toDateString();
            $res_turn_calendar->date_upd               = $now->toDateString();
            $res_turn_calendar->user_add               = 1;
            $res_turn_calendar->save();

            self::calendarPeriodicCaseOnlyReplace($res_turn, $old_turn);
        }

        private static function calendarPeriodicCaseOnly(res_turn $res_turn, res_turn_calendar $old_turn, Collection $pieces,Carbon $date)
        {
            $now = Carbon::now();

            $res_turn_calendar = new res_turn_calendar();
            $res_turn_calendar->res_turn_id             = $res_turn->id;
            $res_turn_calendar->res_type_turn_id    = $res_turn->res_type_turn_id;
            $res_turn_calendar->start_date               = $date->toDateString();
            $res_turn_calendar->end_date                = "9999-12-31";
            $res_turn_calendar->start_time               = $res_turn->hours_ini;
            $res_turn_calendar->end_time                = $res_turn->hours_end;
            $res_turn_calendar->date_add               = $now->toDateString();
            $res_turn_calendar->date_upd               = $now->toDateString();
            $res_turn_calendar->user_add               = 1;
            $res_turn_calendar->save();

        }

        private static function calendarPeriodicCaseOnlyReplace(res_turn $res_turn, res_turn_calendar $old_turn)
        {
            $now = Carbon::now();
            res_turn_calendar::where("res_turn_id", $old_turn->res_turn_id)
                                        ->where("end_date", ">=", $now->toDateString())
                                        ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($old_turn->start_date))
                                        ->update([
                                                "res_type_turn_id"   =>  $res_turn->res_type_turn_id,
                                                "res_turn_id"            =>  $res_turn->id,
                                                "user_upd"               =>  2,
                                                "date_upd"               =>  $now->toDateString(),
                                                "start_time"              =>  $res_turn->hours_ini,
                                                "end_time"               =>  $res_turn->hours_end,
                                            ]);
        }

        private static function createResTurnCalendar($res_turn_id, $res_type_turn_id, $start_date, $end_date, $start_time, $end_time, $date_add, $date_upd, $user_add)
        {
            res_turn_calendar::create([
                            "res_turn_id"           => $res_turn_id,
                            "res_type_turn_id"  => $res_type_turn_id,
                            "start_date"             => $start_date,
                            "end_date"              => $end_date,
                            "start_time"             => $start_time,
                            "end_time"              => $end_time,
                            "date_add"              => $date_add,
                            "date_upd"              => $date_upd,
                            "user_add"              => $user_add
                        ]);
        }

        public static function calendarPeriodicCaseDelete(res_turn $res_turn, res_turn_calendar $old_turn, Collection $pieces, Carbon $date)
        {
            $now = Carbon::now();

            $periodic = res_turn_calendar::where("res_turn_id", $res_turn->id)
                                            ->where("end_date", ">=", $now->toDateString())
                                            ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($old_turn->start_date))
                                            ->orderBy("end_date", "asc")
                                            ->get();

            if ($periodic->count() == 0) return;

            $first_periodic = $periodic->first();

            $start_date_first_periodic = self::date($first_periodic->start_date);

            if ($periodic->count() > 1) {
                // Hay un periodico con fechas desperdiagadas

                if (  $start_date_first_periodic->lt( $date )) {
                    self::calendarPeriodicCaseCut($first_periodic, $date);
                    self::calendarPeriodicCaseDeleteComplete($res_turn, $old_turn);
                } else {
                    self::calendarPeriodicCaseDeleteComplete($res_turn, $old_turn);
                }

            } else if ($periodic->count() ==1 ){
                // Solo hay un una unica fecha periodica

                if (  $start_date_first_periodic->lt( $date )) {
                    self::calendarPeriodicCaseCut($first_periodic, $date);
                    self::calendarPeriodicCaseDeleteComplete($res_turn, $old_turn);
                } else {
                    self::calendarPeriodicCaseDeleteComplete($res_turn, $old_turn);
                }

            }

        }

        private static function calendarPeriodicCaseDeleteComplete(res_turn $res_turn, res_turn_calendar $old_turn)
        {
            $now = Carbon::now();

            res_turn_calendar::where("res_turn_id", $res_turn->id)
                                            ->where("end_date", ">=", $now->toDateString())
                                            ->whereRaw("dayofweek(start_date) = dayofweek(?)", array($old_turn->start_date))
                                            ->delete();
        }

        private static function date(String $date)
        {
            return  Carbon::parse($date);
        }
    }