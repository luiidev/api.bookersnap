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

    public function rules()
    {
        switch ($this->method()) {
            case 'PUT':
            case 'put':
                $rules = $this->rulesDefault();
                break;
            case 'PATCH':
            case 'patch':
                $rules = $this->rulesEdit();
                break;
            default:
                $rules = $this->rulesDefault();
                break;
        }
        return $rules;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rulesDefault()
    {
        // dd($this->time_tolerance);
        return [
            "time_tolerance"       => "required|integer|between:0,180|multiple:5",
            "time_restriction"     => "required|integer|between:0,180|multiple:5",
            "max_people"           => "required|integer|between:0,100",
            "max_people_standing"  => "required|integer|between:0,1000",
            "max_table"            => "required|integer|between:0,20",
            "res_code_status"      => "required|integer|between:0,1",
            "res_privilege_status" => "string",
            // "messenger_status"     => "integer",
            // "date_add"             => "required|date_format:Y-m-d H:i:s",
            // "date_upd"             => "date_format: Y-m-d H:i:s",
            // "user_add"             => "required|integer",
            // "user_upd"             => "integer",
            // "reserve_portal"       => "required|integer",
            "res_percentage_id"    => "required|integer|exists:res_percentage,res_percentage",
            "name_people_1"        => "string",
            "name_people_2"        => "string",
            "name_people_3"        => "string",
            "status_people_1"      => "integer",
            "status_people_2"      => "integer",
            "status_people_3"      => "integer",
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rulesEdit()
    {
        return [
            "time_tolerance"       => "integer|between:0,180|multiple:5",
            "time_restriction"     => "integer|between:0,180|multiple:5",
            "max_people"           => "integer|between:0,100",
            "max_people_standing"  => "integer|between:0,1000",
            "max_table"            => "integer|between:0,20",
            "res_code_status"      => "integer|between:0,1",
            "res_privilege_status" => "string",
            // "messenger_status"     => "integer",
            // "date_add"             => "date_format:Y-m-d H:i:s",
            // "date_upd"             => "date_format: Y-m-d H:i:s",
            // "user_add"             => "integer",
            // "user_upd"             => "integer",
            // "reserve_portal"       => "integer",
            "res_percentage_id"    => "integer|exists:res_percentage,res_percentage",
            "name_people_1"        => "string",
            "name_people_2"        => "string",
            "name_people_3"        => "string",
            "status_people_1"      => "integer",
            "status_people_2"      => "integer",
            "status_people_3"      => "integer",
            // "id"                   => "array",
            // "id.*"                 => "exists:res_form,id",

        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }

}
