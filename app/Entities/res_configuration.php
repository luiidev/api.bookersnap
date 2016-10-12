<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class res_configuration extends Model
{
    protected $table   = "res_configuration";
    protected $id      = "ms_microsite_id";
    public $timestamps = false;
    // protected $hidden   = ['ms_microsite_id'];
    protected $fillable = ['ms_microsite_id', 'time_tolerance', 'time_restriction', 'max_people', 'max_table', 'res_code_status', 'res_privilege_status', 'messenger_status', 'date_add', 'date_upd', 'user_add', 'user_upd', 'reserve_portal', 'res_percentage_id', 'name_people_1', 'name_people_2', 'name_people_3', 'status_people_1', 'status_people_2', 'status_people_3'];

    // public function forms()
    // {
    //     return $this->belongsToMany(res_form::class, "res", "res_form_configuration", "ms_microsite_id");
    // }

    // public function microsite()
    // {
    //     return $this->hasOne(ms_microsite::class);
    // }

}
