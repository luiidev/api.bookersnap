<?php

use Illuminate\Database\Seeder;

class seeder_bs_city_table extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::Table('bs_city')->insert(
            $this->getData()
        );
    }

    private function getData()
    {
        return [
            ['name' => 'Lima', 'bs_country_id' => 'PE'],
            ['name' => 'Arequipa', 'bs_country_id' => 'PE'],
            ['name' => 'Tumbes', 'bs_country_id' => 'PE'],
            ['name' => 'Cuzco', 'bs_country_id' => 'PE']
        ];
    }
}
