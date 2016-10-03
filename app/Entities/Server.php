<?php

namespace App\Entities;

use App\Entities\Table;
use Illuminate\Database\Eloquent\Model;

class Server extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_server';

    public function tables(){
        return $this->hasMany(Table::class, "res_server_id");
    }
}
