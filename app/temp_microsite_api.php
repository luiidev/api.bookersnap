<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace App;

/**
 * Description of temp_microsite_api
 *
 * @author DESKTOP-BS01
 */
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class temp_microsite_api extends Model {

    protected $table = "temp_microsite_api";
    public $timestamps = false;
    
  
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
//        static::addGlobalScope('status', function (Builder $builder) {
//            $builder->where('status', '<>', 2);
//        });
    }
    
    public function microsite() {
        return $this->hasMany('App\Entities\ms_microsite', 'ms_microsite_id');
    }

}
