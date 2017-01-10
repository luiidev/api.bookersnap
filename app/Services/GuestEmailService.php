<?php

namespace App\Services;

use App\res_guest_email;

class GuestEmailService {

    public function saveAll(array $emails, int $guest_id) {
        foreach ($emails as $value) {
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
            $entity = new res_guest_email();
            $entity->res_guest_id = $guest_id;
            $entity->email = $data['email'];
            $entity->save();
        } catch (\Exception $e) {
            //dd($e->getMessage());
            abort(500, $e->getMessage());
        }
        return $entity;
    }

    public function update(array $data, int $guest_id, int $id) {
        try {
            $entity = res_guest_email::where('id', $id)->where('res_guest_id', $guest_id)->first();
            $entity->email = empty($data['email']) ? $entity->email : $data['email'];
            $entity->save();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

}
