<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Utilitarios
 *
 * @author user
 */

namespace App\Helpers;

//use App\Helpers\Utilitarios;

class Utilitarios {

    public static function show($data) {
        echo "<pre>";
        print_r($data);
        echo "</pre>";
        die();
    }

    public static function fromYYYYMMDDtoDDMMYYYYHHMMAMPM($fecha) {

        // Se recibe el siguiente formato 
        // return dd-mm-YYYY hh:mm
        //$AMorPM = date('A', strtotime($fecha));

        if ($fecha != NULL) {
            $newFecha = substr($fecha, 0, 10);
            $hora = date('h:m A', strtotime($fecha));
            return $fechaHora = self::fromYYYMMDDtoDDMMYYY($newFecha) . " " . $hora;
        }
    }

    public static function fromYYYMMDDtoDDMMYYY($yyymmdd, $separador = "-") {
        $nuevafecha = explode($separador, $yyymmdd); // split('[/.-]', $fecha);
        if ($yyymmdd != "") {
            return ($nuevafecha[2] . $separador . $nuevafecha[1] . $separador . $nuevafecha[0]);
        } else {
            return "";
        }
    }

    public static function fromDDMMYYYtoYYYMMDD($ddmmyyy, $separador = "-") {
        $nuevafecha = explode($separador, $ddmmyyy); // split('[/.-]', $fecha);
        if ($ddmmyyy != "") {
            return ($nuevafecha[2] . $separador . $nuevafecha[1] . $separador . $nuevafecha[0]);
        } else {
            return "";
        }
    }

    public static function agregarComidines($palabra) {
        $palabra = addcslashes(trim($palabra), '%_');
        $palabra = str_replace(' ', '%', "%{$palabra}%");
        return $palabra;
    }

    public static function sumarTiempos($time1, $time2) {
      $times = array($time1, $time2);
      $seconds = 0;
      foreach ($times as $time)
      {
        list($hour,$minute,$second) = explode(':', $time);
        $seconds += $hour*3600;
        $seconds += $minute*60;
        $seconds += $second;
      }
      $hours = floor($seconds/3600);
      $seconds -= $hours*3600;
      $minutes  = floor($seconds/60);
      $seconds = "00";
      return "{$hours}:{$minutes}:{$seconds}";
    }

}
