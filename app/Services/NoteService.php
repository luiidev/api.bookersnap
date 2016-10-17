<?php

namespace App\Services;

use App\res_note;
use Carbon\Carbon;

class NoteService
{
    public function saveNote(array $data, $microsite_id)
    {
        $response = null;
        if (isset($data[id]) && $this->exists($ms_microsite_id, $data['date_add'], $data['res_type_turn_id'])) {
            $response = $this->updateNote($data, $ms_microsite_id);
        } else {
            $response = $this->createNote($data . $ms_microsite_id);
        }
        return $response;
    }

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

    public function updateNote(array $data, $microsite_id)
    {
        $note = res_note::where('ms_microsite_id', $microsite_id)
            ->where("res_type_turn_id", $data['res_type_turn_id'])
            ->where("date_add", $data['date_add'])
            ->first();

        $note->texto = $data['texto'];
        $note->save();

        return $note;
    }

    public function getList($microsite_id, $date)
    {
        $rows = res_note::where('ms_microsite_id', $microsite_id)->where("date_add", $date)->get();
        return $rows;
    }

    public function exists($microsite_id, $date, $type_turn_id)
    {
        $response = (res_note::where('ms_microsite_id', $microsite_id)
                ->where("date_add", $date)
                ->where("res_type_turn_id", $type_turn_id)
                ->get()->count() > 0) ? true : false;
        return $response;
    }

}
