<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Services;

/**
 * Description of TypeTurnService
 *
 * @author USER
 */
use App\res_type_turn;

class TypeTurnService {

    public function getList() {
        $rows = res_type_turn::where('status', 1)->get();
        return $rows->toArray();
    }
    
    
}