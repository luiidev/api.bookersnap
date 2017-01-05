<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OldTokenSession extends Model {

    protected $connection = 'mysql_old';
    protected $table = 'token_session';
    public $timestamps = false;
    
    
}