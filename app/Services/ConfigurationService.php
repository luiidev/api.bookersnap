<?php

namespace App\Services;

use App\Entities\res_configuration;
use App\Services\Helpers\ConfigurationHelper;
use Carbon\Carbon;

class ConfigurationService
{
    // private $lang;
    // private $microsite_id;
    // private $request;
    // private $reservation;

    // public function __construct($request)
    // {
    //     $this->request                    = $request;
    //     $this->lang                       = $request->route("lang");
    //     $this->microsite_id               = $request->route("microsite_id");
    //     $this->reservation                = $request->route('reservations');
    //     $this->request["ms_microsite_id"] = $this->microsite_id;
    // }

    // public static function make($request)
    // {
    //     return new static($request);
    // }

    public function getConfiguration(int $microsite_id)
    {
        // $date = Carbon::now('America/Lima');
        return res_configuration::where("ms_microsite_id", $microsite_id)->first();
    }

    public function createDefaultConfiguration(int $microsite_id)
    {
        try {

            $date = Carbon::now('America/Lima');

            $config                       = new res_configuration();
            $config->ms_microsite_id      = $microsite_id;
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

    public function updateConfiguration(int $microsite_id, array $input)
    {
        $config = res_configuration::where('ms_microsite_id', $microsite_id)->first();
        if ($config != null) {
            $date           = Carbon::now('America/Lima');
            $confingRequest = $input;
            unset($confingRequest["_bs_user_id"]);
            $confingRequest["date_upd"] = $date;
            $config->where('ms_microsite_id', $microsite_id)->update($confingRequest);
            $configUpdate = res_configuration::where('ms_microsite_id', $microsite_id)->first();
            return $configUpdate;
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

    public function updateCodeStatus(int $microsite_id, array $input)
    {
        $helper = new ConfigurationHelper();
        $data   = $helper->editConfiguration($input);
        $config = res_configuration::where('ms_microsite_id', $microsite_id)->first();
        if ($config != null) {
            $date             = Carbon::now('America/Lima');
            $data["date_upd"] = $date;
            $config->where('ms_microsite_id', $microsite_id)->update($data);
            $configUpdate = res_configuration::where('ms_microsite_id', $microsite_id)->first();
            return $configUpdate;
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

}
