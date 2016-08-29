<?php

use Illuminate\Database\Seeder;

class seeder_res_guest_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('res_guest')->insert($this->getData());
        DB::table('res_guest_has_res_guest_tag')->insert($this->getDataHasTags());
    }
    private function getData() {
        /* $this->getRow(1, $first_name, $last_name, $birthdate, $genere), */
        return [
            $this->getRow(1, "Josue", "", "1989", "M",1),
            $this->getRow(2, "Mario", "", "1989", "M",1), 
            $this->getRow(3, "Kael", "", "1989", "M",2),          
        ];
    }

    private function getRow(int $id, string $first_name, string $last_name, string $birthdate, string $gender,int $microsite) {
        return [
            'id' => $id, 
            'first_name' => $first_name, 
            'last_name' => $last_name, 
            'birthdate' => $birthdate,
            'gender' => $gender,
            'ms_microsite_id' => $microsite
        ];
    }
    
    private function getDataHasTags() {
        return [
            $this->getRowHasTags(1, 1),
            $this->getRowHasTags(1, 5),
            $this->getRowHasTags(1, 10),
            $this->getRowHasTags(1, 15),
            $this->getRowHasTags(2, 1),
            $this->getRowHasTags(2, 16),
            $this->getRowHasTags(2, 1),
            $this->getRowHasTags(2, 20),
            $this->getRowHasTags(3, 3),
            $this->getRowHasTags(3, 15),
            $this->getRowHasTags(3, 21),
        ];
    }
    private function getRowHasTags($res_guest_id, $res_guest_tag_id) {
        return [
            "res_guest_id" => $res_guest_id,
            "res_guest_tag_id" => $res_guest_tag_id,
        ];
    }
    
}
