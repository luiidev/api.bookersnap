<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Block extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_block';

    public function tables() {
    	return $this->hasMany('App\Entities\BlockTable','res_block_id');
    }

}
