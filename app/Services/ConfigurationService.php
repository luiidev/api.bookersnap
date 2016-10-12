<?php

namespace App\Services;

class ConfigurationService
{
    private $lang;
    private $microsite_id;
    private $request;

    public function __construct($request)
    {
        $this->request                    = $request;
        $this->lang                       = $request->route("lang");
        $this->microsite_id               = $request->route("microsite_id");
        $this->request["ms_microsite_id"] = $this->microsite_id;
    }

    public static function make($request)
    {
        return new static($request);
    }

    public function getConfiguration()
    {

    }

    public function createConfiguration()
    {

    }

    public function updateConfiguration()
    {

    }
}
