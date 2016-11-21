<?php

use Illuminate\Database\Seeder;

class seeder_res_guest_phone_tables extends Seeder
{
	public function run()
    {
        DB::table('res_guest_phone')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(1, "(01)555-011", 1),
            $this->getRow(2, "(01)285-011", 2),
              
        ];
    }

    private function getRow(int $id, string $number, int $res_guest_id) {
        return [
            'id' => $id, 
            'number' => $number, 
            'res_guest_id' => $res_guest_id, 
        ];
    }
}
