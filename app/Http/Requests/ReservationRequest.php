<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class TurnRequest extends Request {

    public function wantsJson() {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        $this->filterInputs();
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
            'email' => 'required|string',
            'hours_ini' => "required",
            'hours_end' => 'required',
            'on_table' => 'integer|in:0,1',
            'early' => 'integer|in:0,1',
            'res_type_turn_id' => 'required|exists:res_type_turn,id',
            'turn_zone' => 'required|array',
            'turn_zone.*.res_zone_id' => 'required|exists:res_zone,id,ms_microsite_id,' . $this->route('microsite_id'),
            'turn_zone.*.res_turn_rule_id' => 'required|exists:res_turn_rule,id',
            'turn_zone.*.tables' => 'array',
            'turn_zone.*.tables.*.id' => 'required|integer|exists:res_table,id',
            'turn_zone.*.tables.*.availability' => 'required|array',
            'turn_zone.*.tables.*.availability.*.rule_id' => 'required|integer|in:-1,0,1,2',
        ];
    }

    private function RulesUpdate() {

        return [
            'id' => 'required|exists:res_turn,id,ms_microsite_id,' . $this->route('microsite_id'),
            'name' => 'required|string',
            'hours_ini' => 'required',
            'hours_end' => 'required',
            'on_table' => 'integer|in:0,1',
            'early' => 'integer|in:0,1',
            'res_type_turn_id' => 'required|exists:res_type_turn,id',
            'turn_zone' => 'required|array',
            'turn_zone.*.res_zone_id' => 'required|exists:res_zone,id,ms_microsite_id,' . $this->route('microsite_id'),
            'turn_zone.*.res_turn_rule_id' => 'required|exists:res_turn_rule,id',
            'turn_zone.*.tables' => 'array',
            'turn_zone.*.tables.*.id' => 'required|integer|exists:res_table,id',
            'turn_zone.*.tables.*.availability' => 'required|array',
            'turn_zone.*.tables.*.availability.*.rule_id' => 'required|integer|in:-1,0,1,2',
        ];
    }

    public function filterInputs() {
        
        list($hours, $minute) = explode(":", $this->input('hours_ini'));
        $this->request->set('hours_ini', "$hours:$minute:00");
                
        list($hours, $minute) = explode(":", $this->input('hours_end'));        
        $this->request->set('hours_end', "$hours:$minute:00");
        
    }


}
