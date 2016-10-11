<?php

namespace App\Services;

use App\res_tag_r;

class ReservationTagService
{
    private $lang;
    private $microsite_id;
    private $request;

    public function __construct($request)
    {
        $this->request = $request;
        $this->lang = $request->route("lang");
        $this->microsite_id = $request->route("microsite_id");

        $this->request["ms_microsite_id"] = $this->microsite_id;
    }

    public static function make($request) {
        return new static($request);
    }

    public function get_tags()
    {
        return res_tag_r::where("ms_microsite_id", $this->microsite_id)->get(array("id", "name", "status"));
    }

    public function create_tag()
    {
        res_tag_r::create($this->request->all());
    }

    public function destroy_tag()
    {
        res_tag_r::destroy($this->request->route("tag"));
    }
}