<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
            $this->dataDemo();
    }

    public function dataDemo(){
        $this->call(seeder_bs_country_table::class);
        $this->call(seeder_bs_city_table::class);
        $this->call(seeder_bs_user_table::class);
        $this->call(seeder_ms_microsite_table::class);
        
        $this->call(seeder_res_type_turn_table::class);
        $this->call(seeder_res_zone_table::class);
        $this->call(seeder_res_table_table::class);
        $this->call(seeder_res_turn_table::class);
        $this->call(seeder_res_day_turn_zone_table::class);  
    }
}
