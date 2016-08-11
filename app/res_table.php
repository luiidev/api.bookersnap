<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class res_table extends Model
{
    protected $table = "res_table";
     public $timestamps = false;
    protected $hidden = ['res_zone_id', 'user_add', 'user_upd', 'date_add', 'date_upd'];
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

}
