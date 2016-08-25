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
        
         switch ($this->method()) {
            case 'POST':
            case 'post':
                $rules = $this->RulesStore();
                break;
            case 'PUT':
            case 'put':
                $rules = $this->RulesUpdate();
                break;
            default:
                $rules = [];
                break;
        }
        return $rules;
    }
    
    private function RulesStore() {
        return [            
            "name"=> "required|string",
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
            "tables.*.config_color"=> "regex:#fff",
            //"tables.*.config_position"=> "regex:/^[0-9]+$/,/^[0-9]+$/",
            "tables.*.config_forme"=> 'integer|in:1,2,3',
            "tables.*.config_size"=> 'integer|in:1,2,3',
            "tables.*.config_rotation"=> 'integer'
        ];
    }
    
    private function RulesUpdate() {
        
        return [
            'id' => 'required|exists:res_zone,id,ms_microsite_id,'.$this->route('microsite_id'),
            "name"=> "required|string",
            "sketch"=> 'integer|in:0,1',
            "status"=> 'integer|in:0,1',
            "type_zone"=> 'integer|in:0,1',
            "join_table"=> 'integer|in:0,1',
            "status_smoker"=> 'integer|in:0,1',
            "people_standing"=> 'integer',
           //'tables' => 'required|array', 
            "tables.*.id"=> 'exists:res_table,id,res_zone_id,'.$this->route('zone_id'),
            "tables.*.name"=> "required",
            "tables.*.min_cover"=> 'required|integer',
            "tables.*.max_cover"=> 'required|integer',
            "tables.*.price"=> 'float',
            "tables.*.status"=> 'integer|in:0,1,2',
            "tables.*.config_color"=> "#fff",
            //"tables.*.config_position"=> "regex:/^[0-9]+$/,/^[0-9]+$/",
            "tables.*.config_forme"=> 'integer|in:1,2,3',
            "tables.*.config_size"=> 'integer|in:1,2,3',
            "tables.*.config_rotation"=> 'integer'
        ];
    }
    
    public function messages()
    {
        return [
            
        ];
    }

}