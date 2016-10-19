<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class ms_microsite extends Model
{
    protected $table = "ms_microsite";

    public function privileges()
    {
        return $this->belongsToMany('App\Entities\bs_user', 'res_privilege', 'ms_microsite_id', 'bs_user_id')->withPivot('date_add', 'user_add');
    }

    public function creator()
    {
        return $this->belongsTo('App\Entities\bs_user', 'bs_user_id', 'id');
    }
}
