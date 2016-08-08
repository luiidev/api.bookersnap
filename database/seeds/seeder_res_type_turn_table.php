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
            $this->getRow(1, "Desayuno"),
            $this->getRow(2, "Almuerzo"),
            $this->getRow(3, "Cena"),
            $this->getRow(4, "Bar")
        ];
    }

    private function getRow(int $id, string $name) {
        return ['id' => $id, 'name' => $name, 'status' => 1];
    }

}
