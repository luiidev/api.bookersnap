<?php

use Illuminate\Database\Seeder;

class seeder_res_guest_tag_category_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('res_guest_tag_category')->insert($this->getData());
    }
    private function getData() {
        return [
            $this->getRow(1, "Estado especial", 1),
            $this->getRow(2, "Alergias", 1), 
            $this->getRow(3, "Restricciones", 1),
            $this->getRow(4, "Personalizados", 1)
        ];
    }

    private function getRow(int $id, string $name, string $status) {
        return [
            'id' => $id, 
            'name' => $name, 
            'status' => $status
        ];
    }
}
