<?php

namespace App\Services;

use App\res_guest_phone;

class GuestPhoneService {

    public function saveAll(array $phones, int $guest_id) {
        foreach ($phones as $value) {
            $this->save($value, $guest_id);
        }
    }

    public function save(array $data, int $guest_id) {
        $id = empty($data['id']) ? null : $data['id'];
        if ($id === null) {
            $this->create($data, $guest_id);
        } else {
            $this->update($data, $guest_id, $id);
        }
    }

    public function create(array $data, int $guest_id) {
        try {
            $entity = new res_guest_phone();
            $entity->res_guest_id = $guest_id;
            $entity->number = $data['number'];
            $entity->save();
        } catch (\Exception $e) {
            //dd($e->getMessage());
            abort(500, $e->getMessage());
        }
        return $entity;
    }

    public function update(array $data, int $guest_id, int $id) {
        try {
            $entity = res_guest_phone::where('id', $id)->where('res_guest_id', $guest_id)->first();
            $entity->number = empty($data['number']) ? $entity->number : $data['number'];
            $entity->save();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

}
