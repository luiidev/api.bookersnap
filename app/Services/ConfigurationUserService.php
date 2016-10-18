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

    public function getAllUser(string $serach)
    {
        $users = bs_user::orWhere('firstname', 'LIKE', '%' . $serach . '%')
            ->orWhere('lastname', 'LIKE', '%' . $serach . '%')
            ->orWhere('email', 'LIKE', '%' . $serach . '%')->select('id', 'firstname', 'lastname', 'email', 'photo')
            ->get();
        return $users;
    }

    public function savePrivilegeUser(int $microsite_id, int $user_id, int $user_add)
    {
        $date      = new Carbon();
        $microsite = ms_microsite::where('id', $microsite_id)->first();
        $exists    = $this->buscarUser($microsite, $user_id);
        if (!$exists) {
            $microsite->privileges()->attach($user_id, ["date_add" => $date->now(), "user_add" => $user_add]);
            $user = $microsite->privileges()->where('id', $user_id)->get();
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
