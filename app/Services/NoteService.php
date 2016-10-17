<?php

namespace App\Services;

use App\res_note;
use Carbon\Carbon;

class NoteService
{
    public function createNote(array $data, $microsite_id)
    {
        $note = new res_note();

        $note->texto            = $data['texto'];
        $note->date_add         = Carbon::now();
        $note->ms_microsite_id  = $microsite_id;
        $note->res_type_turn_id = $data['res_type_turn_id'];

        $note->save();

        return $note;
    }

    public function getList($microsite_id, $date)
    {
        $rows = res_note::where('ms_microsite_id', $microsite_id)->where("date_add", $date)->get();
        return $rows;
    }

}
