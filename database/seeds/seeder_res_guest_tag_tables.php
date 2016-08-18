<?php

use Illuminate\Database\Seeder;

class seeder_res_guest_tag_tables extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('res_guest_tag')->insert($this->getData());
    }
    private function getData() {
        return [
            $this->getRow(1, 1, "VIP", 1),
            $this->getRow(2, 1, "Press", 1), 
            $this->getRow(3, 1, "Regular", 1),  
            $this->getRow(4, 1, "Inversor", 1),
            $this->getRow(5, 1, "Bar Regular", 1), 
            $this->getRow(6, 1, "High Spender", 1),
            $this->getRow(7, 1, "Friends & Family", 1),
            $this->getRow(8, 2, "Soya", 1), 
            $this->getRow(9, 2, "Leche", 1),
            $this->getRow(10, 2, "Pescado", 1),
            $this->getRow(11, 2, "Wheat", 1), 
            $this->getRow(12, 2, "Gluten", 1),
            $this->getRow(13, 2, "Peanuts", 1),
            $this->getRow(14, 2, "Tree Nuts", 1), 
            $this->getRow(15, 2, "Shellfish", 1),
            $this->getRow(16, 3, "Vegan", 1),
            $this->getRow(17, 3, "Paleo", 1),
            $this->getRow(18, 3, "Kosher", 1),
            $this->getRow(19, 3, "Vegetarian", 1),
            $this->getRow(20, 3, "Macrobiotic", 1),
            $this->getRow(21, 3, "Pescetarian", 1),
        ];
    }

    private function getRow(int $id, int $res_guest_tag_category_id, string $name, int $status) {
        return [
            'id' => $id, 
            'name' => $name, 
            'status' => $status,
            'res_guest_tag_gategory_id' => $res_guest_tag_category_id
        ];
    }
}
