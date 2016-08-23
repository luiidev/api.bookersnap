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
        /* REGISTROS DE TABLAS MAESTRAS*/
        $this->call(seeder_bs_country_table::class);
        $this->call(seeder_bs_city_table::class);
        /* Sistema de mesas */
        $this->call(seeder_res_type_turn_table::class);
        $this->call(seeder_res_turn_rule_table::class);
        $this->call(seeder_res_guest_tag_category_tables::class);
        $this->call(seeder_res_guest_tag_tables::class);
        $this->call(seeder_res_reservation_status_tables::class);
        
        /* RESGISTROS DE PRUEBA*/
        $this->call(seeder_bs_user_table::class);
        $this->call(seeder_ms_microsite_table::class);
        
                
        $this->call(seeder_res_guest_tables::class);
        $this->call(seeder_res_guest_email_tables::class);
        $this->call(seeder_res_guest_phone_tables::class);
        $this->call(seeder_res_zone_table::class);
        $this->call(seeder_res_table_table::class);
        $this->call(seeder_res_turn_table::class);
        $this->call(seeder_res_turn_zone_tables::class);
        $this->call(seeder_res_turn_zone_table_tables::class);
        $this->call(seeder_res_reservation_tables::class);
        $this->call(seeder_res_table_reservation_tables::class);
    }
}
