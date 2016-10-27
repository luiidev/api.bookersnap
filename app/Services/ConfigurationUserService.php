<?php

namespace App\Services;

use App\Entities\bs_user;
use App\Entities\ms_microsite;
use Carbon\Carbon;

class ConfigurationUserService
{
    public function getPrivilegeUsers(int $microsite_id)
    {
        $microsite = ms_microsite::where('id', $microsite_id)->first();
        $users     = $microsite->privileges()->with('socials')->get();

        return $users;
    }

    public function getAllUser(int $microsite_id, $search)
    {
        $microsite      = ms_microsite::where('id', $microsite_id)->first();
        $privilegeUsers = $microsite->privileges()->select('id', 'firstname', 'lastname', 'email', 'photo')->get();
        $allUsers       = bs_user::orWhereRaw("concat(firstname,' ',lastname) LIKE ?", array('%' . $search . '%'))
            ->orWhere('email', 'LIKE', '%' . $search . '%')
            ->select('id', 'firstname', 'lastname', 'email', 'photo')
            ->get();
        // return $allUsers;

        $diff     = $allUsers->diff($privilegeUsers);
        $filtered = $diff->filter(function ($value, $key) {
            return $key < 10;
        });

        return $filtered;
    }

    public function savePrivilegeUser(int $microsite_id, int $user_id, int $user_add)
    {
        $date      = new Carbon();
        $microsite = ms_microsite::where('id', $microsite_id)->first();
        $exists    = $this->buscarUser($microsite, $user_id);
        if (!$exists) {
            $microsite->privileges()->attach($user_id, ["date_add" => $date->now(), "user_add" => $user_add]);
            $user = $microsite->privileges()->with('socials')->where('id', $user_id)->first();
            return $user;
        } else {
            abort(409, "Este privilegio ya esta registrado para este usuario");
        }

    }

    public function deletePrivilegeUSer(int $microsite_id, int $user_id)
    {
        $microsite = ms_microsite::where('id', $microsite_id)->first();
        $exists    = $this->buscarUser($microsite, $user_id);
        if ($exists) {
            $microsite->privileges()->detach($user_id);
            return true;
        } else {
            abort(404, "El usuario no cuenta con privilegio registrado");
        }
    }

    private function buscarUser(ms_microsite $microsite, int $user_id)
    {
        $exists = $microsite->privileges()->where('id', $user_id)->exists();
        return $exists;
    }
}
