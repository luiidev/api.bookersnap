<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_table extends Model
{
    protected $table = "res_table";
    protected $fillable = ['res_zone_id', 'name', 'min_cover', 'price', 'status', 'config_color', 'config_position', 'config_forme', 'config_size', 'config_rotation', 'date_add', 'date_upd', 'user_add', 'user_upd'];
    protected $hidden = ['res_zone_id', 'user_add', 'user_upd', 'date_add', 'date_upd'];
}
