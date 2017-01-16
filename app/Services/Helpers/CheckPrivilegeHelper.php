<?php

namespace App\Services\Helpers;

use App\Entities\bs_role;
use App\Entities\ms_manager;
use App\Services\Helpers\HttpRequestHelper;
use Redis;

class CheckPrivilegeHelper
{
    const userPrefix = "user:";
    const rolePrefix = "role:";

    /**
     * Mostrar role de usuario por sitio en cache
     * @param  Int $bs_user_id       id de usuario
     * @param  Int $ms_mp_id         id de sitio
     * @param  Int $bs_type_admin_id tipo de sitio
     * @return String
     */
    public function getRole($bs_user_id, $ms_mp_id, $bs_type_admin_id)
    {
        $key = self::userPrefix.$bs_user_id.":".$ms_mp_id.":".$bs_type_admin_id;

        return Redis::get($key);
    }

    /**
     * Mostar privilegios de usuario por id de usuario, sitio y tipo de sitio
     * @param  Int $bs_user_id       id de usuario
     * @param  Int $ms_mp_id         id de sitio
     * @param  Int $bs_type_admin_id tipo de sitio
     * @return Array                   [description]
     */
    public function getPrivileges($bs_user_id, $ms_mp_id, $bs_type_admin_id)
    {
        $user_role_id = $this->getRole($bs_user_id, $ms_mp_id, $bs_type_admin_id);

        if ($user_role_id !== null) {
            $privileges = Redis::lrange(self::rolePrefix.$user_role_id, 0, -1);
            if (count($privileges) === 0) { // Si no se encuentra el rol, preguntar a api auth
                return $this->getAuthPrivileges($bs_user_id, $ms_mp_id, $bs_type_admin_id);
            } else {    // Si todo esta ok devuelve array de privilegios
                return Redis::lrange(self::rolePrefix.$user_role_id, 0, -1);
            }
        } else {    // Si no se ecnuentra el usuario, preguntar en api auth
            return $this->getAuthPrivileges($bs_user_id, $ms_mp_id, $bs_type_admin_id);
        }
    }

/**
 * Si no se encuentra usuario o roles se consulta a api auth
 * @param  Int $bs_user_id       id de usuario
 * @param  Int $ms_mp_id         id de sitio
 * @param  Int $bs_type_admin_id tipo de sitio
 * @return Array                   [description]
 */
    public function getAuthPrivileges($bs_user_id, $ms_mp_id, $bs_type_admin_id)
    {
            $http = HttpRequestHelper::make("GET")
                ->setUrl(config('settings.api_auth')."/v1/es/microsites/".$ms_mp_id."/permissions")
                ->setData([
                    "bs_user_id" => $bs_user_id,
                ])
                ->send();

            if ($http->isOk()) {
                $data = @$http->getArrayResponse()["data"];
                return $data ? $data : [];
            } else {
                return [];
            }
    }

    /**
     * Mostar privilegios de rol por id de rol
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getPrivilegesByRole($id)
    {
        // return Redis::lrange(self::rolePrefix.$id, 0, -1);
    }

    /**
     * Actualizar los privilegios de un role por el id de role
     * @param  Int $id
     * @return Void
     */
    public function updateRole($id)
    {
        // Redis::del(self::rolePrefix.$id);

        // $role = bs_role::select("id")->with(["privileges" => function($query) {
        //     return $query->select("action_name");
        // }])->find($id);

        // if ($role !== null) {
        //     if (count($role->privileges)) {
        //         Redis::rpush(self::rolePrefix.$role->id, $role->privileges->pluck("action_name")->toArray());
        //     }
        // }
    }

    /**
     * Volver a generar todos los privilegios por rol
     * @return Void
     */
    public function resetRoles()
    {
        // Redis::eval("local keys = redis.call('keys', ARGV[1]) for i=1,#keys,5000 do redis.call('del', unpack(keys, i, math.min(i+4999, #keys))) end return keys", 0, self::rolePrefix.'*');

        // $roles = bs_role::select("id")->with(["privileges" => function($query) {
        //     return $query->select("action_name");
        // }])->get();

        // foreach ($roles as $role) {
        //     if (count($role->privileges)) {
        //         Redis::rpush(self::rolePrefix.$role->id, $role->privileges->pluck("action_name")->toArray());
        //     }
        // }
    }

    /**
     * Eliminar usuario y sitios asociados de cache
     * @param  Int $id 
     * @return Void
     */
    public function deleteUser($id)
    {
        // Redis::eval("local keys = redis.call('keys', ARGV[1]) for i=1,#keys,5000 do redis.call('del', unpack(keys, i, math.min(i+4999, #keys))) end return keys", 0, self::userPrefix.$id.':*');
    }

    /**
     * Eliminar un role de cache
     * @param  Int $id 
     * @return Void
     */
    public function deleteRole($id)
    {
        // Redis::del(self::rolePrefix.$id);
    }

}