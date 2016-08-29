<?php

use Illuminate\Database\Seeder;

class seeder_res_turn_zone_tables extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_turn_zone')->insert($this->getData());
    }

    private function getData() {
        /* Turn, Zona, TurnRule */
        return [
            $this->getRow(1, 1, 1),
            $this->getRow(1, 2, 1),
            $this->getRow(1, 3, 1),
            $this->getRow(1, 4, 1),
            $this->getRow(1, 5, 1),
            $this->getRow(3, 1, 1),
            $this->getRow(3, 2, 1),
            $this->getRow(3, 3, 1),
            $this->getRow(3, 4, 1),
            $this->getRow(3, 5, 1),
            $this->getRow(4, 1, 1),
            $this->getRow(4, 2, 1),
            $this->getRow(4, 3, 1),
            $this->getRow(4, 4, 1),
            $this->getRow(4, 5, 1),
            $this->getRow(4, 6, 1)
        ];
    }

    private function getRow(int $res_turn_id, int $zone_id, int $res_turn_rule_id) {
        return [            
            'res_turn_id' => $res_turn_id,
            'res_zone_id' => $zone_id,
            'res_turn_rule_id' => $res_turn_rule_id
        ];
    }

}
