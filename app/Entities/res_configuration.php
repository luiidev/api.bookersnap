<?php

namespace App\Entities;

use App\Entities\res_form;
use Illuminate\Database\Eloquent\Model;

class res_configuration extends Model
{
    const CREATED_AT = "date_add";
    const UPDATED_AT = "date_upd";
    protected $table   = "res_configuration";
    protected $primaryKey      = "ms_microsite_id";
    
    // protected $hidden   = ['ms_microsite_id'];
    protected $fillable = ['ms_microsite_id', 'time_tolerance', 'time_restriction', 'max_people', 'max_table', 'res_code_status', 'res_privilege_status', 'messenger_status', 'user_add', 'user_upd', 'reserve_portal', 'res_percentage_id', 'name_people_1', 'name_people_2', 'name_people_3', 'status_people_1', 'status_people_2', 'status_people_3'];

    public function forms()
    {
        return $this->belongsToMany(res_form::class, 'res_form_configuration', 'ms_microsite_id','res_form_id');
    }

    // public function microsite()
    // {
    //     return $this->hasOne(ms_microsite::class);
    // }

    public function default(int $microsite_id){
        $this->ms_microsite_id      = $microsite_id;
        $this->time_tolerance       = 1;
        $this->time_restriction     = 1;
        $this->max_people           = 1;
        $this->max_people_standing  = 1;
        $this->max_table            = 1;
        $this->res_code_status      = 1;
        $this->res_privilege_status = 0;
        $this->messenger_status     = 1;
        $this->user_add             = 1;
        $this->user_upd             = 1;
        $this->reserve_portal       = 1;
        $this->res_percentage_id    = 1;
        $this->name_people_1        = "Hombres";
        $this->name_people_2        = "Mujeres";
        $this->name_people_3        = "Niños";
        $this->status_people_1      = 1;
        $this->status_people_2      = 1;
        $this->status_people_3      = 1;
        return $this;
    }

}
