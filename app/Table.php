<?php namespace App;
  
use Illuminate\Database\Eloquent\Model;
  
class Table extends Model
{    
	protected $table = "res_tables";
    protected $fillable = ['res_zone_id','name', 'min_cover', 'price', 'status', 'config_color', 'config_position', 'config_forme', 'config_size', 'config_rotation', 'date_add', 'date_upd','user_add','user_upd'];  


    /*public function Zone(){
        return $this->belongsTo(Zone::class, 'res_zone_id');
    }*/
}
?>