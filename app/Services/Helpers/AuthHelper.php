<?php

namespace App\Services\Helpers;

use App\Entities\bs_user_session;
use Redis;

class AuthHelper 
{
    const sessionPrefix = "session:";
    const expire = 129600;

    public static  function getSession(string $session_id, int $expire = self::expire)
    {
        $key = self::sessionPrefix.$session_id;
        $user_id = Redis::get($key);
        if ($user_id) {
            Redis::expire($key, $expire);
        }

        return $user_id;
    }
}
