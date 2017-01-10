<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App;

/**
 * Description of res_source_type
 *
 * @author USER
 */

use Illuminate\Database\Eloquent\Model;

class res_source_type extends Model {
    
    const _ID_HOSSTES = 1;
    const _ID_PORTAL = 2;
    const _ID_PHONE = 3;
    const _ID_WEB = 4;
    
    protected $table = "res_source_type";
    public $timestamps = false;
    
}