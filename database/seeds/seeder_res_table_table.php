<?php

/*
 * php artisan db:seed --class=seeder_res_table_table 
 */

use Illuminate\Database\Seeder;

class seeder_res_table_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_table')->insert($this->getData());
    }

    private function getData() {
        $res_table = array();
        for ($i = 1; $i <= 4; $i++) {
            for ($j = 1; $j <= 10; $j++) {
                $res_table[] = $this->getRow($i, "Z" . $i . "M" . $j);
            }
        }
        return $res_table;
    }

    private function getRow(int $zone_id, string $name) {
        return [
            'res_zone_id' => $zone_id,
            'name' => $name,
            'min_cover' => rand(1, 5),
            'max_cover' => rand(6, 10),
            'price' => 0,
            'status' => 1,
            'config_color' => "#fff",
            'config_position' => rand(100, 500) . "," . rand(100, 500),
            'config_forme' => rand(1, 3),
            'config_size' => rand(1, 3),
            'config_rotation' => 45,
            'date_add' => Carbon\Carbon::now(),
            'date_upd' => null,
            'user_add' => 1,
            'user_upd' => null
        ];
    }

}
