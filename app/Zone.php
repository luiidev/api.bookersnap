<?php namespace App;
  
use Illuminate\Database\Eloquent\Model;
  
class Zone extends Model
{    
	$table = "res_zone";
     protected $fillable = ['name', 'sketch', 'status', 'type_zone', 'join_table', 'status_smoker', 'people_standing', 'user_add', 'user_upd', 'ev_event_id', 'ms_microsite_id'];
}
?>