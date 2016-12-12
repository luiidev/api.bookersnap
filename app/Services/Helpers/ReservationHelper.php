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
        $value        = $this->validateOrderBy($value);
        $sort         = [];
        $order        = explode(".", $value);
        $sort['type'] = $order[1];

        switch ($order[0]) {
            case 'time':
                $sort['value'] = 'hours_reservation';
                break;
            case 'status':
                $sort['value'] = 'res_reservation_status_id';
                break;
            case 'covers':
                $sort['value'] = 'num_guest';
                break;
            case 'guest':
                $sort['value'] = 'guest.first_name';
                break;
            case 'table':
                $sort['value'] = 'table.name';
                break;
            default:
                $sort['value'] = 'hours_reservation';
                break;
        }
        return (object) $sort;
    }

    private function validateOrderBy(string $value)
    {
        $pos = strpos($value, ".");

        if ($pos === false) {
            $value = $value . ".asc";
        }

        return $value;
    }

}
