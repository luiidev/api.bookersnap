<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Domain;

use Carbon\Carbon;

/**
 * Description of TimeForTable
 *
 * @author USER
 */
class TimeForTable
{

    /*
     */
    public static function timeToIndex(string $time, $left = true)
    {
        if($left){
            return date("H", strtotime($time)) * 4 + (int)((date("i", strtotime($time))) / 15);
        }else{ 
            $index = date("H", strtotime($time)) * 4 + (int)((date("i", strtotime($time))) / 15);
            return (((date("i", strtotime($time))) % 15) > 0)?$index+1:$index;
        }
    }

    public static function indexToTime($index)
    {
        return Carbon::today()->addMinutes(15*$index)->toTimeString();
        // return $index * 60 * 15;
        // return date("H:i:s", $index * 60 * 15);
    }
}
