<?php

namespace App\Services;

use App\res_tag_r;

class ReservationTagService
{
    public function get_tags(int $microsite_id)
    {
        $display = array("id", "name", "status");
        return res_tag_r::where("ms_microsite_id", $microsite_id)
            ->get($display);
    }

    public function create_tag(int $microsite_id, string $name)
    {
        $tag                  = new res_tag_r();
        $tag->name            = $name;
        $tag->ms_microsite_id = $microsite_id;
        $tag->status          = 1;
        $tag->save();
        return $tag;
    }

    public function destroy_tag(int $idTag)
    {
        res_tag_r::destroy($idTag);
    }
}
