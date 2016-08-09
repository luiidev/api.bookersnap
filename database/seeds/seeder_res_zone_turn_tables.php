<?php

use Illuminate\Database\Seeder;

class seeder_res_zone_turn_tables extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_zone_turn')->insert($this->getData());
    }

    private function getData() {
        /* Zona, Turn, Typeturn, Microsite */
        return [
            $this->getRow(1, 1, 1, 1),
            $this->getRow(1, 3, 2, 1),
            $this->getRow(1, 5, 3, 1),
            $this->getRow(1, 7, 4, 1),
            $this->getRow(2, 1, 1, 1),
            $this->getRow(2, 3, 2, 1),
            $this->getRow(2, 5, 3, 1),
            $this->getRow(2, 7, 4, 1),
            $this->getRow(3, 2, 1, 1),
            $this->getRow(3, 4, 2, 1),
            $this->getRow(3, 6, 3, 1),
            $this->getRow(3, 8, 4, 1),
            $this->getRow(4, 2, 1, 1),
            $this->getRow(4, 4, 2, 1),
            $this->getRow(4, 6, 3, 1),
            $this->getRow(4, 8, 4, 1)
        ];
    }

    private function getRow(int $zone_id, int $res_turn_id, int $res_type_turn_id, int $ms_microsite_id) {
        return [
            'res_zone_id' => $zone_id,
            'res_turn_id' => $res_turn_id,
            'res_type_turn_id' => $res_type_turn_id,
            'ms_microsite_id' => $ms_microsite_id
        ];
    }

}
