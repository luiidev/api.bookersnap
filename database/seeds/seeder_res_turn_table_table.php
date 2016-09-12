<?php

use Illuminate\Database\Seeder;

class seeder_res_turn_table_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_turn_table')->insert($this->getData());
    }

    private function getData() {
        /* Turn, Zona, TurnRule */
        return [
            $this->getRow("7:00:00", "9:00:00", 1, 1, 1),
            $this->getRow("7:00:00", "9:00:00", 2, 1, 1),
            $this->getRow("7:00:00", "9:00:00", 3, 1, 1),
            $this->getRow("7:00:00", "9:00:00", 4, 1, 1),
            $this->getRow("7:00:00", "9:00:00", 5, 1, 1),
        ];
    }

    private function getRow(string $start_time, string $end_time, int $res_table_id, int $res_turn_id, int $res_turn_rule_id) {
        return [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'res_table_id' => $res_table_id,
            'res_turn_id' => $res_turn_id,
            'res_turn_rule_id' => $res_turn_rule_id
        ];
    }

}
