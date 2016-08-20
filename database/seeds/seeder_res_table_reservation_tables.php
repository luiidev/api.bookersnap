<?php

use Illuminate\Database\Seeder;

class seeder_res_table_reservation_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('res_table_reservation')->insert($this->getData());
    }
    private function getData() {
        return [
            $this->getRow(1, 1, 1),
            $this->getRow(1, 8, 1),
            $this->getRow(2, 2, 2),
            $this->getRow(3, 3, 3),
            $this->getRow(4, 4, 4),
            $this->getRow(5, 5, 5),
            $this->getRow(6, 6, 6),
            $this->getRow(7, 1, 6),
            $this->getRow(8, 2, 6),
        ];
    }

    private function getRow(int $res_reservation_id, int $res_table_id, int $num_people) {
        return [
            'num_people' => $num_people, 
            'res_table_id' => $res_table_id, 
            'res_reservation_id' => $res_reservation_id
        ];
    }
}
