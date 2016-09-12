<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Domain;

/**
 * Description of TimeForTable
 *
 * @author USER
 */
class TimeForTable {
    
    public static function timeToIndex(string $time) {        
        return date("H", strtotime($time)) * 4 + (date("i", strtotime($time))) / 15;
    }
    
    public static function indexToTime($index) {
        return date("H:i:s", $index * 60 * 15);
    }
}
