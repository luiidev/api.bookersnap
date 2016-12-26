<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class res_zone extends Model {

    protected $table = "res_zone";
    public $timestamps = false;
//    protected $fillable = ['name', 'sketch', 'status', 'type_zone', 'join_table', 'status_smoker', 'people_standing', 'user_add', 'user_upd', 'ev_event_id', 'ms_microsite_id'];
//    protected $hidden = ['ms_microsite_id', 'user_add', 'user_upd', 'date_upd'];
  
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('status', function (Builder $builder) {
            $builder->where('status', '<>', 2);
        });
    }
    
    public function tables() {
        return $this->hasMany('App\res_table', 'res_zone_id');
    }

    public function turns() {
        return $this->belongsToMany('App\res_turn', 'res_turn_zone', 'res_zone_id', 'res_turn_id');
    }
    
    public function turnZone() {
        return $this->hasMany('App\res_turn_zone', 'res_zone_id');
    }

}
