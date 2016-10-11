<?php

namespace App\Services;

use App\res_tag_r;

class ReservationTagService extends Service
{
    public function __construct($request)
    {
        parent::__construct($request);
    }

    public function get_tags()
    {
        $display = array("id", "name", "status");
        return res_tag_r::where("ms_microsite_id", $this->microsite_id)
                                            ->get($display);
    }

    public function create_tag()
    {
        $tag = new res_tag_r();
        $tag->name = $this->req->name;
        $tag->name = $this->microsite_id;
        $tag->status = 1;
        $tag->save();
        return $tag;
    }

    public function destroy_tag()
    {
        res_tag_r::destroy($this->tag);
    }
}
