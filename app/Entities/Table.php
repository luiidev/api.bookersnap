<?php

namespace App\Entities;

use App\Entities\Server;
use Illuminate\Database\Eloquent\Model;

class Table extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_table';

    public function server(){
        return $this->belongsTo(Server::class);
    }
}
