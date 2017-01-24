<?php

namespace App\Services;

use App\Entities\res_form;

class ConfigurationFormService extends Service
{
    public function list()
    {
        return res_form::all();
    }
}