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

    private function getData3() {
        $res_turn_zone = array();
        for ($i = 1; $i < $items; $i++) {
            for ($j = 1; $j <= 3; $j++) {
                $res_turn_zone[] = array(
                    'name' => 0,
                    'on_table' => 0,
                    'hours_ini' => "7:00",
                    'hours_end' => "11:00",
                    'status' => 1,
                    'date_add' => Carbon\Carbon::now(),
                    'date_upd' => null,
                    'user_add' => 1,
                    'user_upd' => null,
                    'early' => 0,
                    'res_zone_id' => $i,
                    'ms_microsite_id' => 1,
                    'res_type_turn_zone_id' => $j,
                );
            }
        }
    }
    private function getData() {
        return [
            $this->getRow(1, 1, 1, "Turno D1", "7:00:00", "10:00:00"),
            $this->getRow(1, 1, 1, "Turno D2", "7:00:00", "10:00:00"),
            $this->getRow(1, 2, 1, "Turno A1", "12:00:00", "16:00:00"),
            $this->getRow(1, 2, 1, "Turno A2", "12:00:00", "16:00:00"),
            $this->getRow(1, 3, 1, "Turno C1", "17:00:00", "22:00:00"),
            $this->getRow(1, 3, 1, "Turno C2", "17:00:00", "22:00:00"),
            $this->getRow(1, 4, 1, "Turno B1", "20:00:00", "23:00:00"),
            $this->getRow(1, 4, 1, "Turno B2", "20:00:00", "2:00:00"),
        ];
    }

    private function getRow(int $id, int $type_turn, int $microsite_id, string $name, string $startdate, string $enddate) {
        return [
            'id' => $id,
            'name' => $name,
            'on_table' => 0,
            'hours_ini' => "7:00",
            'hours_end' => "11:00",
            'status' => 1,
            'date_add' => Carbon\Carbon::now(),
            'date_upd' => null,
            'user_add' => 1,
            'user_upd' => null,
            'early' => 0,
            'res_type_turn_id' => $type_turn,
            'ms_microsite_id' => $microsite_id,
        ];
    }

}
