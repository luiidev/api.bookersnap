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
}
