<?php

use Illuminate\Database\Seeder;

class seeder_res_reservation_status_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_reservation_status')->insert($this->getData());
    }
    
    private function getData() {
        return [
            $this->getRow(1, "No confirmado", "#ccc"),
            $this->getRow(2, "Confirmado", "green"),
            $this->getRow(3, "Mensaje enviado", "orange"),
            $this->getRow(4, "sin respuesta", "orange"),
            $this->getRow(5, "Numero equivocado", "red"),
            $this->getRow(6, "Llegando tarde", "red"),
            $this->getRow(7, "Pago rechazado", "#ccc"),
            $this->getRow(8, "Pago realizado", "green"),
            $this->getRow(9, "Cancelado / invitado", "red"),
            $this->getRow(10, "Cancelado / restaurante", "red"),
            $this->getRow(11, "Ausente", "red"),
            $this->getRow(12, "Finalizado", "red"),
        ];
    }

    private function getRow(int $id, string $name, string $color) {
        return [
            'id' => $id, 
            'name' => $name, 
            'color' => $color, 
            'status' => 1,
            'user_add' => 1,
            'date_add' => Carbon\Carbon::now()
        ];
    }
}
