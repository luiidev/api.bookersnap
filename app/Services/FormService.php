<?php

namespace App\Services;

use App\Entities\res_form;
use App\Entities\res_form_configuration;

class FormService
{

    public function getFormsByMicrosite($microsite)
    {
        $forms = res_form::all();
        $ms_forms = res_form_configuration::where("ms_microsite_id", $microsite)->get();

        $forms->each(function(&$item) {
            $item->status = 0;
        });

        foreach ($forms as $item) {
            foreach ($ms_forms as $form) {
                if ($form->res_form_id == $item->id) {
                    $item->status = 1;
                    break;
                }
            }
        }

        return $forms;
    }
   
}
