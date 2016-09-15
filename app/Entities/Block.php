<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class Block extends Model {

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = 'res_block';

    public function tables() {
    	return $this->hasMany('App\Entities\BlockTable','res_block_id');
    	//return $this->hasOne('App\res_table_reservation');
        //return $this->belongsTo('App\res_table_reservation', 'id');
        //return $this->belongsToMany('App\res_table', 'res_table_reservation', 'res_reservation_id', 'res_table_id');
        //return $this->hasMany('App\res_table_reservation', 'res_table_id');
    }

}
