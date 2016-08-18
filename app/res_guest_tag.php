<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_guest_tag extends Model {

    protected $table = "res_guest_tag";
    public $timestamps = false;
    //protected $fillable = [];
    protected $hidden = ['pivot'];

    public function category() {
       return $this->belongsTo('App\res_guest_tag_category', 'res_guest_tag_category_id');
    }

}
