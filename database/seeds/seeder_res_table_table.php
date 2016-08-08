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

        $res_table = array();
        for ($i = 1; $i < 10; $i++) {
            for ($j = 1; $j < 5; $j++) {
                $res_table[] = array(
                    'res_zone_id' => $i,
                    'name' => str_random(10),
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
                );
            }
        }

        DB::table('res_table')->insert($res_table);
    }

}
