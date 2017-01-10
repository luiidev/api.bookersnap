<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class bs_userlogin extends Model
{
    protected $table = "bs_userlogin";

    public function user()
    {
        return $this->belongsTo('App\Entities\bs_user', 'bs_user_id', 'id');
    }
    public function social()
    {
        return $this->belongsTo('App\Entities\bs_socialnetwork', 'bs_socialnetwork_id', 'id');
    }
}
