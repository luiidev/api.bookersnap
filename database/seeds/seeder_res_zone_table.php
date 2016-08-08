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
        $res_zone = array();
        for ($i = 1; $i < 10; $i++) {
            $res_zone[] = array(
                'name' => "ZONA $i",
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
                'ms_microsite_id' => 1
            );
        }
    }

}
