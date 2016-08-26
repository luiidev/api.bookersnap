<?php

/*
 * php artisan db:seed --class=seeder_res_turn_zone_table 
 */

use Illuminate\Database\Seeder;

class seeder_res_turn_rule_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_turn_rule')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(99, "Disabled"),       
            $this->getRow(1, "In House Only"), 
            $this->getRow(2, "Online"),             
        ];
    }

    private function getRow(int $id, string $name) {
        return [
            'id' => $id,
            'name' => $name,
            'status' => 1,
        ];
    }

}
