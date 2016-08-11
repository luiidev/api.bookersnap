<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Requests;

/**
 * Description of ConfigZoneRequest
 *
 * @author USER
 */
use App\Http\Requests\Request;

class ZoneRequest extends Request {

    public function wantsJson()
    {
        return true;
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            "name"=> "required",
            "sketch"=> 'integer|in:0,1',
            "status"=> 'integer|in:0,1',
            "type_zone"=> 'integer|in:0,1',
            "join_table"=> 'integer|in:0,1',
            "status_smoker"=> 'integer|in:0,1',
            "people_standing"=> 'integer',
           //'tables' => 'required|array',            
            "tables.*.name"=> "required",
            "tables.*.min_cover"=> 'required|integer',
            "tables.*.max_cover"=> 'required|integer',
            "tables.*.price"=> 'float',
            "tables.*.status"=> 'integer|in:0,1,2',
            "tables.*.config_color"=> "#fff",
            //"tables.*.config_position"=> "regex:/^[0-9]+$/,/^[0-9]+$/",
            "tables.*.config_forme"=> 'integer|in:1,2,3',
            "tables.*.config_size"=> 'integer|in:1,2,3',
            "tables.*.config_rotation"=> 'integer',        
        ];
    }
    
    public function messages()
    {
        return [
            
        ];
    }

}