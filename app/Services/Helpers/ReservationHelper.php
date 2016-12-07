<?php

namespace App\Services\Helpers;

/**
 * Helpers para manejo de reservaciones
 */
class ReservationHelper
{
    //En la url no poner el campo a filtrar si no un alias, devuelve el campo real
    public function getNameSort(string $value)
    {
        $sort = "";
        switch ($value) {
            case 'time':
                $sort = 'hours_reservation';
                break;
            case 'status':
                $sort = 'res_reservation_status_id';
                break;
            case 'covers':
                $sort = 'num_guest';
                break;
            case 'guest':
                $sort = 'guest.first_name';
                break;
            case 'table':
                $sort = 'table.name';
                break;

            default:
                # code...
                break;
        }
        return $sort;
    }

}
