<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Block extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_block';


}
