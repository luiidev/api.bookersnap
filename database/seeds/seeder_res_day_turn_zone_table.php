<?php

use Illuminate\Database\Seeder;

class seeder_res_day_turn_zone_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        $res_day_turn_zone = array();
        for ($j = 1; $j <= 3; $j++) {
            for ($k = 1; $k <= 6; $k++) {
                $res_day_turn_zone[] = array(
                    'day' => $k,
                    'res_turn_zone_id' => $j,
                    'res_zone_id' => 1,
                    'ms_microsite_id' => 1,
                );
            }
        }
        
        for ($j = 4; $j <= 6; $j++) {
            for ($k = 1; $k <= 6; $k++) {
                $res_day_turn_zone[] = array(
                    'day' => $k,
                    'res_turn_zone_id' => $j,
                    'res_zone_id' => 2,
                    'ms_microsite_id' => 1,
                );
            }
        }
        DB::table('res_day_turn_zone')->insert($res_day_turn_zone);
    }

}
