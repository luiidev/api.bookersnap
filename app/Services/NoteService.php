<?php

namespace App\Services;

use App\res_note;
use Carbon\Carbon;

class NoteService extends Service
{
    public function create(array $data, $microsite_id)
    {
        $note = new res_note();

        $note->texto            = $data['texto'];
        $note->date_add         = Carbon::now();
        $note->ms_microsite_id  = $microsite_id;
        $note->res_type_turn_id = $data['res_type_turn_id'];

        $note->save();

        return $note;
    }

}
