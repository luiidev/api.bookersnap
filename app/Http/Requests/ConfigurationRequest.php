<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ConfigurationRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            "time_tolerance"    => "required|integer",
            "time_restriction"  => "required|integer",
            "max_people"        => "required|integer",
            "max_table"         => "required|integer",
            // "res_code_status"      => "required|integer",
            // "res_privilege_status" => "string",
            // "messenger_status"     => "integer",
            // "date_add"             => "required|date_format:Y-m-d H:i:s",
            // "date_upd"             => "date_format: Y-m-d H:i:s",
            // "user_add"             => "required|integer",
            // "user_upd"             => "integer",
            // "reserve_portal"       => "required|integer",
            "res_percentage_id" => "required|integer|exists:res_percentage,res_percentage",
            "name_people_1"     => "string",
            "name_people_2"     => "string",
            "name_people_3"     => "string",
            "status_people_1"   => "integer",
            "status_people_2"   => "integer",
            "status_people_3"   => "integer",
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }

}
