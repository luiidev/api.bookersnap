<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_guest_tag_category extends Model {

    protected $table = "res_guest_tag_category";
    public $timestamps = false;
    //protected $fillable = [];
    
    public function tags() {
       return $this->hasMany('App\res_guest_tag', 'res_guest_tag_gategory_id');
    }
}
