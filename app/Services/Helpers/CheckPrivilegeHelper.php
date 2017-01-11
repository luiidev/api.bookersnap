<?php

namespace App\Services\Helpers;

use App\Entities\bs_role;
use App\Entities\ms_manager;
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

        $role_id = Redis::get($key);

        if ($role_id === null) {
            $http = HttpRequestHelper::make("GET", "http://localhost:100/v1/es/manager")
                ->setData([
                    "bs_user_id" => 1,
                    "ms_mp_id" => 1,
                    "bs_type_admin_id" => 2
                ])
                ->send();

            if ($http->isOk()) {
                $data = $http->getArrayResponse()["data"];

                if ($data !== null) {
                    if ($data->bs_role_id !== null) {
                        Redis::set($key, $data->bs_role_id);
                        Redis::expire($key, 129600);
                        $role_id = $data->bs_role_id;
                    }
                }
            }
        }

        return $role_id;
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
        $role_id = $this->getRole($bs_user_id, $ms_mp_id, $bs_type_admin_id);

        if ($role_id !== null) {
            return Redis::lrange(self::rolePrefix.$role_id, 0, -1);
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
        return Redis::lrange(self::rolePrefix.$id, 0, -1);
    }

    /**
     * Actualizar los privilegios de un role por el id de role
     * @param  Int $id
     * @return Void
     */
    public function updateRole($id)
    {
        Redis::del(self::rolePrefix.$id);

        $role = bs_role::select("id")->with(["privileges" => function($query) {
            return $query->select("action_name");
        }])->find($id);

        if ($role !== null) {
            if (count($role->privileges)) {
                Redis::rpush(self::rolePrefix.$role->id, $role->privileges->pluck("action_name")->toArray());
            }
        }
    }

    /**
     * Volver a generar todos los privilegios por rol
     * @return Void
     */
    public function resetRoles()
    {
        Redis::eval("local keys = redis.call('keys', ARGV[1]) for i=1,#keys,5000 do redis.call('del', unpack(keys, i, math.min(i+4999, #keys))) end return keys", 0, self::rolePrefix.'*');

        $roles = bs_role::select("id")->with(["privileges" => function($query) {
            return $query->select("action_name");
        }])->get();

        foreach ($roles as $role) {
            if (count($role->privileges)) {
                Redis::rpush(self::rolePrefix.$role->id, $role->privileges->pluck("action_name")->toArray());
            }
        }
    }

    /**
     * Eliminar usuario y sitios asociados de cache
     * @param  Int $id 
     * @return Void
     */
    public function deleteUser($id)
    {
        Redis::eval("local keys = redis.call('keys', ARGV[1]) for i=1,#keys,5000 do redis.call('del', unpack(keys, i, math.min(i+4999, #keys))) end return keys", 0, self::userPrefix.$id.':*');
    }

    /**
     * Eliminar un role de cache
     * @param  Int $id 
     * @return Void
     */
    public function deleteRole($id)
    {
        Redis::eval("local keys = redis.call('keys', ARGV[1]) for i=1,#keys,5000 do redis.call('del', unpack(keys, i, math.min(i+4999, #keys))) end return keys", 0, self::rolePrefix.$id);
    }

}