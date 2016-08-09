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
            $this->getRow(1, "Josue", "", "1989", "M"),
            $this->getRow(2, "Mario", "", "1989", "M"),
            $this->getRow(3, "Marx", "", "1989", "M"),
            $this->getRow(4, "Obed", "", "1989", "M"),
            $this->getRow(5, "Mark", "", "1989", "M"),
            $this->getRow(6, "Cristofer", "", "1989", "M"),            
        ];
    }

    private function getRow(int $id, int $first_name, int $last_name, string $birthdate, string $genere) {
        return [
            'id' => $id, 
            'first_name' => $first_name, 
            'last_name' => $last_name, 
            'birthdate' => $birthdate,
            'genere' => $genere,    
            'user_add' => 1,
            'date_add' => Carbon\Carbon::now()
        ];
    }
}
