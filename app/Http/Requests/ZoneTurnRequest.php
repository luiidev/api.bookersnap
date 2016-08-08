<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ZoneTurnRequest extends Request {

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
            'token' => 'required',
            'microsite_id' =>  'required|exists:ms_microsite,id',
            'user_id' => 'required|exists:bs_user,id',
            'hours_ini' => 'required',
            'hours_end' => 'required',
            'status' => 'integer|in:0,1',
            'on_table' => 'integer|in:0,1',
            'early' => 'integer|in:0,1',
            'type_turn_id' => 'required|exists:res_type_turn_zone,id',
            'days' => 'required|array',
            'days.*.day' => 'integer|in:0,1,2,3,4,5,6',           
        ];
    }
    
    public function messages()
    {
        return [
            'hours_ini.required' => 'Ingrese hora de inicio.',
            'hours_end.required' => 'Ingresar hora de cierre.',
            'status.regex' => 'Ingresar estado.',
            'on_table.regex' => 'Ingresar on_table.',
            'early.regex' => 'Ingresar early.',
            'days.required' => 'Elejir dias para el turno.',
            'days.*.day.in' => 'Elejir un día de la semana turno.',
            'type_turn_id.required' => 'Tipo de turno requerido.',
            'type_turn_id.exists' => 'El tipo de turno no existe.',
            'microsite_id.required' => 'Micrositio requerido.',
            'microsite_id.exists' => 'El micrositio no existe.',
            'user_id.required' => 'Usuario requerido.',
            'user_id.exists' => 'El usuario ingresado no existe.'
        ];
    }

}
