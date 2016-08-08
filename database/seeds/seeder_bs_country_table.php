<?php

use Illuminate\Database\Seeder;

class seeder_bs_country_table extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::Table('bs_country')->insert(
            $this->getData()
        );
    }

    private function getData()
    {
        return [
            ['id' => 'PE', 'name' => 'Perú', 'status' => 1],
            ['id' => 'MX', 'name' => 'México', 'status' => 1],
            ['id' => 'RU', 'name' => 'Rusia', 'status' => 1],
            ['id' => 'BR', 'name' => 'Brasil', 'status' => 1]
        ];
    }
}
