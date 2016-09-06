<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class BlockTable extends Model {

    protected $primaryKey = 'res_block_id';
    public $timestamps = false;
    protected $table = 'res_block_table';


}
