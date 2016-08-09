<?php

use Illuminate\Database\Seeder;

class seeder_res_day_turn_zone_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        /*Zone, Turn, Typeturn, Microsite, Days*/
        DB::table('res_day_turn_zone')->insert($this->getData(1, 1, 1, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(1, 3, 2, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(1, 5, 3, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(1, 7, 4, 1, array(1, 2, 3, 4, 5)));

        DB::table('res_day_turn_zone')->insert($this->getData(2, 1, 1, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(2, 3, 2, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(2, 5, 3, 1, array(1, 2, 3, 4, 5)));
        DB::table('res_day_turn_zone')->insert($this->getData(2, 7, 4, 1, array(1, 2, 3, 4, 5)));
    }

    private function getData(int $res_zone_id, int $res_turn_id, int $res_type_turn_id, int $ms_microsite_id, array $days) {
        $turnDay = [];
        foreach ($days as $key => $day) {
            $turnDay[] = $this->getRow($res_zone_id, $res_turn_id, $res_type_turn_id, $ms_microsite_id, $day);
        }
        return $turnDay;
    }

    private function getRow(int $res_zone_id, int $res_turn_id, int $res_type_turn_id, int $ms_microsite_id, int $day, string $dateini = '2016-08-01', string $dateend = null) {
        return [
            'res_zone_id' => $res_zone_id,
            'res_turn_id' => $res_turn_id,
            'res_type_turn_id' => $res_type_turn_id,
            'ms_microsite_id' => $ms_microsite_id,
            'day' => $day,
            'date_ini' => $dateini,
            'date_end' => $dateend
        ];
    }

}
