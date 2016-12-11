<?php

namespace App\Services;

use App\res_note;
use Carbon\Carbon;

class NoteService
{
    public function saveNote(array $data, int $microsite_id, string $date)
    {
        $response = null;
        $data['date_add'] = $date;
        if ($this->exists($microsite_id, $date, $data['res_type_turn_id'])) {
            $response = $this->updateNote($data, $microsite_id, $date);
        } else {
            $response = $this->createNote($data, $microsite_id, $date);
        }
        return $response;
    }

    public function createNote(array $data, int $microsite_id, string $date)
    {
        $note = new res_note();

        $note->texto            = $data['texto'];
        $note->date_add         = $date;
        $note->ms_microsite_id  = $microsite_id;
        $note->res_type_turn_id = $data['res_type_turn_id'];

        $note->save();

        return $note;
    }

    public function updateNote(array $data, int $microsite_id, string $date)
    {
        $note = res_note::where('ms_microsite_id', $microsite_id)
            ->where("res_type_turn_id", $data['res_type_turn_id'])
            ->where("date_add", $date)
            ->first();

        $note->texto = $data['texto'];
        $note->save();

        return $note;
    }

    public function getList(int $microsite_id, string $date)
    {
        $rows = res_note::where('ms_microsite_id', $microsite_id)->where("date_add", $date)->get();
        return $rows;
    }

    public function exists(int $microsite_id, string $date, int $type_turn_id)
    {
        $response = (res_note::where('ms_microsite_id', $microsite_id)
                ->where("date_add", $date)
                ->where("res_type_turn_id", $type_turn_id)
                ->get()->count() > 0) ? true : false;
        return $response;
    }

}
