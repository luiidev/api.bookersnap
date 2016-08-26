<?php

use Illuminate\Database\Seeder;

class seeder_res_type_turn_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('res_type_turn')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(1, "Desayuno", '#689d30'),
            $this->getRow(2, "Almuerzo", '#d7b61c'),
            $this->getRow(3, "Cena", '#4e8dcc'),
            $this->getRow(4, "Bar", '#d8736e')
        ];
    }

    private function getRow(int $id, string $name, string $color) {
        return [
            'id' => $id,
            'name' => $name,
            'color' => $color,
            'status' => 1
        ];
    }

}
