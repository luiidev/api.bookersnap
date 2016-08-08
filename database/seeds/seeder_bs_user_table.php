<?php

use Illuminate\Database\Seeder;

class seeder_bs_user_table extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::Table('bs_user')->insert(
            $this->getData(10)
        );
    }

    private function getData(int $cant)
    {
        $data = [];
        for ($i = 0; $i < $cant; $i++) {
            $data[] = [
                'email' => str_random(10) . '@gmail.com',
                'firstname' => str_random(15),
                'lastname' => str_random(20)
            ];
        }
        return $data;
    }
}
