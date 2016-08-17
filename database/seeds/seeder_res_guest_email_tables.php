<?php

use Illuminate\Database\Seeder;

class seeder_res_guest_email_tables extends Seeder
{
  
    public function run()
    {
        DB::table('res_guest_email')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow(1, "joper30@gmail.com", 1),
            $this->getRow(2, "kael30@gmail.com", 2),
              
        ];
    }

    private function getRow(int $id, string $first_name, int $res_guest_id) {
        return [
            'id' => $id, 
            'email' => $first_name, 
            'res_guest_id' => $res_guest_id, 
        ];
    }
}
