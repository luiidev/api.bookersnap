<?php

namespace App\Services;

use App\Entities\ms_microsite;
use App\Entities\res_form;
use App\Services\FormService;

class ConfigurationFormService extends Service
{
    public function list()
    {
        return FormService::make()->getFormsByMicrosite(1);
    }

    public function updateForm()
    {
        $sync = array();
        foreach ($this->req->form as$value) {
            if ($value["status"] == 1) {
                array_push($sync, $value["id"]);
            }
        }

        $microsite = ms_microsite::find($this->microsite_id);
        if (!is_null($microsite)) {
            $microsite->form()->sync($sync);
        }
    }
}