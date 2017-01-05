<?php

namespace App\Services;

use App\Entities\res_configuration;
use App\Entities\res_form;
use App\Services\Helpers\ConfigurationHelper;

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
        $configuration = res_configuration::where("ms_microsite_id", $microsite_id)->with(['forms' => function ($query) {
            $query->where('status', 1);
        }])->first();
        if($configuration){
            return $configuration;
        }
        return $this->createDefaultConfiguration($microsite_id);
    }

    public function createDefaultConfiguration(int $microsite_id)
    {
            $config                       = new res_configuration();
            $config->ms_microsite_id      = $microsite_id;
            $config->time_tolerance       = 0;
            $config->time_restriction     = 30;
            $config->max_people           = 100;
            $config->max_people_standing  = 10;
            $config->max_table            = 4;
            $config->res_code_status      = 0;
            $config->res_privilege_status = 0;
            $config->messenger_status     = 0;
            $config->user_add             = 1;
            $config->user_upd             = 1;
            $config->reserve_portal       = 1;
            $config->res_percentage_id    = 1;
            $config->name_people_1        = "Hombres";
            $config->name_people_2        = "Mujeres";
            $config->name_people_3        = "Niños";
            $config->status_people_1      = 1;
            $config->status_people_2      = 1;
            $config->status_people_3      = 1;
            $config->save();
            return $config;
    }

    public function updateConfiguration(int $microsite_id, array $input)
    {
        $config = res_configuration::where('ms_microsite_id', $microsite_id)->first();
        if ($config != null) {
            $confingRequest = $input;
            unset($confingRequest["_bs_user_id"]);
            unset($confingRequest["timezone"]);
            // return $confingRequest;
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
            $config->where('ms_microsite_id', $microsite_id)->update($data);
            $configUpdate = res_configuration::where('ms_microsite_id', $microsite_id)->first();
            return $configUpdate;
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

    public function addFormConfiguration(int $microsite_id, array $input)
    {
        $config = res_configuration::where('ms_microsite_id', $microsite_id)->with(['forms' => function ($query) {
            return $query->where('status', 1);
        }])->first();
        if (isset($config)) {
            $config->forms()->attach($input);
            $aux = res_configuration::where('ms_microsite_id', $microsite_id)->with(['forms' => function ($query) {
                return $query->where('status', 1);
            }])->first();
            return $aux->forms->pluck('id');
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

    public function deleteFormConfiguration(int $microsite_id, array $input)
    {
        $config = res_configuration::where('ms_microsite_id', $microsite_id)->with(['forms' => function ($query) {
            return $query->where('status', 1);
        }])->first();
        if (isset($config)) {
            $config->forms()->detach($input);
            $aux = res_configuration::where('ms_microsite_id', $microsite_id)->with(['forms' => function ($query) {
                return $query->where('status', 1);
            }])->first();
            return $aux->forms->pluck('id');
        } else {
            abort(500, "No existe configuracion para ese microsite");
        }
    }

    public function getForm(int $microsite_id)
    {
        $config = res_form::where('status', 1)->with('configurations')->get();
        $form = res_form::where('status', 1)->get();
        return $config->map(function($item) use ($microsite_id){
            if(!$item->configurations->where('ms_microsite_id',$microsite_id)->isEmpty()){
                $item['status'] = 1; 
                unset($item['configurations']);
            }else{
                $item['status'] = 0;
                unset($item['configurations']);
            }
            return $item;

        });


        return $form;
    }

}
