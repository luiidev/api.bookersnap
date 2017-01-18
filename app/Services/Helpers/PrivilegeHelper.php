<?php

namespace App\Services\Helpers;

use App\Entities\bs_role;
use App\Entities\ms_manager;
use App\Services\Helpers\HttpRequestHelper;
use Redis;

class PrivilegeHelper
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
    public static function getRole($bs_user_id, $ms_mp_id, $bs_type_admin_id)
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
    public static function getPrivileges($bs_user_id, $ms_mp_id, $bs_type_admin_id)
    {
        $user_role_id = self::getRole($bs_user_id, $ms_mp_id, $bs_type_admin_id);

        if ($user_role_id !== null) {
            $privileges = Redis::lrange(self::rolePrefix.$user_role_id, 0, -1);
            if (count($privileges) === 0) { // Si no se encuentra el rol, preguntar a api auth
                return self::getAuthPrivileges($bs_user_id, $ms_mp_id);
            } else {    // Si todo esta ok devuelve array de privilegios
                return Redis::lrange(self::rolePrefix.$user_role_id, 0, -1);
            }
        } else {    // Si no se ecnuentra el usuario, preguntar en api auth
            return self::getAuthPrivileges($bs_user_id, $ms_mp_id);
        }
    }

/**
 * Si no se encuentra usuario o roles se consulta a api auth
 * @param  Int $bs_user_id       id de usuario
 * @param  Int $ms_mp_id         id de sitio
 * @param  Int $bs_type_admin_id tipo de sitio
 * @return Array                   [description]
 */
    public static function getAuthPrivileges($bs_user_id, $ms_mp_id)
    {
            $http = HttpRequestHelper::make("GET")
                ->setUrl(config('settings.API_AUTH_URL')."/es/microsites/".$ms_mp_id."/permissions")
                ->setData([
                    "user_id" => $bs_user_id,
                ])
                ->send();

            if ($http->isOk()) {
                $data = @$http->getArrayResponse()["data"];
                return $data ? $data : [];
            } else {
                return [];
            }
    }
}