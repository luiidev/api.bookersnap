<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class seeder_ms_microsite_table extends Seeder {

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() {
        DB::table('ms_microsite')->insert($this->getData());
    }

    private function getData() {
        return [
            $this->getRow('don-titos', 'http://don-titos.com', 'don-titos', null),
            $this->getRow('rokys', 'http://rokysito.com', 'rokysito-surco', null),
            $this->getRow('rokys', 'http://rokysito.com', 'rokysito-aviacion', null),
            $this->getRow('rustikita', 'http://rustikita.com', 'rustikita-aviacion', null)
        ];
    }

    private function getRow($name, $domain = null, $site_name = null, $sitename_free = null) {
        return ['name' => $name, 'domain' => $domain, 'site_name' => $site_name,
            'sitename_free' => $sitename_free, 'date_add' => Carbon::now(), 'user_add' => 1,
            'status_claimed' => 0, 'origin_request' => 0, 'bs_city_id' => 1, 'bs_country_id' => 'PE'];
    }

}
