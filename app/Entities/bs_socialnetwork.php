<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class bs_socialnetwork extends Model
{
    protected $table = "bs_socialnetwork";

    public function logins()
    {
        return $this->hasToMany('App\Entities\bs_userlogin', 'bs_socialnetwork_id');
    }
}
