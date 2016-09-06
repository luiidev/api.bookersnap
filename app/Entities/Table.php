<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Table extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_table';

}
