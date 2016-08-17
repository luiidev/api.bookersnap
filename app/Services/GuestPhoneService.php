<?php

namespace App\Services;

use App\res_guest_phone;

class GuestPhoneService {

    public function save($guest, array $data) {
        $id = empty($data['id']) ? null : $data['id'];
        if ($id === null) {
            $this->create($guest, $data);
        } else {
            $this->update($guest, $data, $id);
        }
    }

    public function create($guest, array $data) {
        try {
            $entity = new res_guest_phone();
            $entity->res_guest_id = $guest->id;
            $entity->number = $data['number'];
            $entity->save();
        } catch (\Exception $e) {
            //dd($e->getMessage());
            abort(500, $e->getMessage());
        }
        return $entity;
    }

    public function update($guest, array $data, int $id) {
        try {
            $entity = res_guest_phone::where('id', $id)->where('res_guest_id', $guest->id)->first();
            $entity->number = empty($data['number']) ? $entity->number : $data['number'];
            $entity->save();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

}
