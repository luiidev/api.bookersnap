<?php

use Illuminate\Database\Seeder;

class seeder_res_reservation_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('res_reservation')->insert($this->getData());
    }
    private function getData() {
        /* $this->getRow($id, $ms_microsite_id, $res_guest_id, $date_reservation, $hours_reservation, $hours_duration, $num_people), */
        return [
            $this->getRow(1, 1, 1, "2016-08-09", "7:00:00", "1:15:00", 2),
            $this->getRow(2, 1, 2, "2016-08-09", "7:00:00", "1:15:00", 2),            
            $this->getRow(3, 1, 3, "2016-08-09", "12:00:00", "1:15:00", 2),
            $this->getRow(4, 1, 4, "2016-08-09", "12:00:00", "1:15:00", 2),
            $this->getRow(5, 1, 5, "2016-08-09", "17:00:00", "1:15:00", 2),
            $this->getRow(6, 1, 6, "2016-08-09", "17:00:00", "1:15:00", 2),
            
            $this->getRow(7, 1, 1, "2016-08-09", "16:00:00", "1:15:00", 2),
            $this->getRow(8, 1, 2, "2016-08-09", "16:00:00", "1:15:00", 2),
        ];
    }

    private function getRow(int $id, int $ms_microsite_id, int $res_guest_id, string $date_reservation, string $hours_reservation, string $hours_duration, int $num_people) {
        return [
            'id' => $id, 
            'ms_microsite_id' => $ms_microsite_id, 
            'bs_user_id' => 1, 
            'res_guest_id' => $res_guest_id,
            'res_reservation_status_id' => 1,
            'date_reservation' => $date_reservation,
            'hours_reservation' => $hours_reservation,
            'hours_duration' => $hours_duration,
            'num_people' => $num_people,
            'status_released' => 1,
            'type_reservation' => 2,
            'user_add' => 1,
            'date_add' => Carbon\Carbon::now()
        ];
    }
}
