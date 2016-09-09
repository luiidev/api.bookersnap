<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ReservationRequest extends Request {

    public function wantsJson() {
        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        //$this->filterInputs();
        return true;
    }

    public function formatErrors(\Illuminate\Contracts\Validation\Validator $validator) {
        $data["response"] = false;
        $data["jsonError"] = $validator->errors()->all();
        $data["data"] = array();
        return $data;
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
            'date_reservation' => "required",
            'hours_reservation' => 'required',
            'num_people' => 'required|integer'
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
            'res_type_turn_id' => 'required|exists:res_type_turn,id'
        ];
    }

    /*public function filterInputs() {
        
        list($hours, $minute) = explode(":", $this->input('hours_ini'));
        $this->request->set('hours_ini', "$hours:$minute:00");
                
        list($hours, $minute) = explode(":", $this->input('hours_end'));        
        $this->request->set('hours_end', "$hours:$minute:00");
        
    }*/


}
