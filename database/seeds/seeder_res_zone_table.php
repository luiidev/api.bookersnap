<?php

/*
 * php artisan db:seed --class=seeder_res_zone_table 
 */

use Illuminate\Database\Seeder;

class seeder_res_zone_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {

        DB::table('res_zone')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(1, 1, "ZONA 1"),
            $this->getRow(2, 1, "ZONA 2"),
            $this->getRow(3, 1, "ZONA 3"),
            $this->getRow(4, 1, "ZONA 4"),
            $this->getRow(5, 1, "ZONA 6"),
            $this->getRow(6, 2, "ZONA 1"),
            $this->getRow(7, 2, "ZONA 2"),
            $this->getRow(8, 2, "ZONA 3"),
            $this->getRow(9, 2, "ZONA 4"),
            $this->getRow(10, 2, "ZONA 5")
        ];
    }

    private function getRow(int $id, int $microsite_id, string $name) {
        return [
            'id' => $id,
            'name' => $name,
            'sketch' => null,
            'status' => 1,
            'type_zone' => 1,
            'join_table' => 1,
            'status_smoker' => 0,
            'people_standing' => rand(1, 100),
            'date_add' => Carbon\Carbon::now(),
            'date_upd' => null,
            'user_add' => 1,
            'user_upd' => null,
            'ms_microsite_id' => $microsite_id
        ];
    }

}
