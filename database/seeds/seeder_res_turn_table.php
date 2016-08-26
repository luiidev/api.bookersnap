<?php

/*
 * php artisan db:seed --class=seeder_res_turn_zone_table 
 */

use Illuminate\Database\Seeder;

class seeder_res_turn_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_turn')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(1, 1, 1, "Turno D1", "7:00:00", "10:00:00"),
            $this->getRow(2, 1, 1, "Turno D2", "7:00:00", "10:00:00"),
            $this->getRow(3, 2, 1, "Turno A1", "12:00:00", "16:00:00"),
            $this->getRow(4, 2, 1, "Turno A2", "12:00:00", "16:00:00"),
            $this->getRow(5, 3, 1, "Turno C1", "17:00:00", "20:00:00"),
            $this->getRow(6, 3, 1, "Turno C2", "17:00:00", "20:00:00"),
            $this->getRow(7, 4, 1, "Turno B1", "20:00:00", "23:00:00"),
            $this->getRow(8, 4, 1, "Turno B2", "20:00:00", "2:00:00"),
        ];
    }

    private function getRow(int $id, int $type_turn_id, int $microsite_id, string $name, string $startdate, string $enddate) {
        $ate_now = \Carbon\Carbon::now();
        $user_id = 1;
        return [
            'id' => $id,
            'name' => $name,
            'on_table' => 0,
            'hours_ini' => $startdate,
            'hours_end' => $enddate,
            'status' => 1,
            'date_add' => $ate_now,
            'date_upd' => $ate_now,
            'user_add' => $user_id,
            'user_upd' => $user_id,
            'early' => 0,
            'res_type_turn_id' => $type_turn_id,
            'ms_microsite_id' => $microsite_id,
        ];
    }

}
