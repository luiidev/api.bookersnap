<?php

namespace App\Services;

use App\Entities\res_configuration;
use Carbon\Carbon;

class ConfigurationService
{
    private $lang;
    private $microsite_id;
    private $request;
    private $reservation;

    public function __construct($request)
    {
        $this->request                    = $request;
        $this->lang                       = $request->route("lang");
        $this->microsite_id               = $request->route("microsite_id");
        $this->reservation                = $request->route('reservation');
        $this->request["ms_microsite_id"] = $this->microsite_id;
    }

    public static function make($request)
    {
        return new static($request);
    }

    public function getConfiguration()
    {
        // $date = Carbon::now('America/Lima');
        return res_configuration::where("ms_microsite_id", $this->microsite_id)->first();
    }

    public function createDefaultConfiguration()
    {
        try {

            $date = Carbon::now('America/Lima');

            $config                       = new res_configuration();
            $config->ms_microsite_id      = $this->microsite_id;
            $config->time_tolerance       = 1;
            $config->time_restriction     = 1;
            $config->max_people           = 1;
            $config->max_table            = 1;
            $config->res_code_status      = 1;
            $config->res_privilege_status = "test";
            $config->messenger_status     = 1;
            $config->date_add             = $date;
            $config->date_upd             = $date;
            $config->user_add             = 1;
            $config->user_upd             = 1;
            $config->reserve_portal       = 1;
            $config->res_percentage_id    = 1;
            $config->name_people_1        = "test";
            $config->name_people_2        = "test";
            $config->name_people_3        = "test";
            $config->status_people_1      = 1;
            $config->status_people_2      = 1;
            $config->status_people_3      = 1;
            $config->save();
            return $config;

        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

    }

    public function updateConfiguration()
    {
        $config = res_configuration::where('ms_microsite_id', $this->reservation)->first();
        if ($config != null) {
            $date        = Carbon::now('America/Lima');
            $testRequest = $this->request->all();
            unset($testRequest["_bs_user_id"]);
            $testRequest["date_upd"] = $date;
            $config->where('ms_microsite_id', $this->reservation)->update($testRequest);
            $configUpdate = res_configuration::where('ms_microsite_id', $this->reservation)->first();
            return $configUpdate;
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

}
