<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_table extends Model
{
    protected $table = "res_table";
     public $timestamps = false;
    protected $hidden = ['res_zone_id', 'user_add', 'user_upd', 'date_add', 'date_upd', 'pivot'];
    //public $appends = ['newfield', 'estado'];


    /*-------------
	// agregar nuevos atributos , virtual
    --------------*/
    public function getNewfieldAttribute(){
    	return $this->config_forme*4;
    }

    public function getEstadoAttribute(){
    	return $this->status;
    }
    
    public function turns() {
        return $this->hasMany('App\res_turn_table', 'res_table_id');
    }

    

}
