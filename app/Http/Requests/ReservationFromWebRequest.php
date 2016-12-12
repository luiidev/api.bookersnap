<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ReservationFromWebRequest extends Request
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
            "token" =>  "required|exists:res_table_reservation_temp",
            "guest" => "required|array",
            "guest.first_name"    =>  "required|string|",
            "guest.last_name" =>  "required|string",
            "guest.email" =>  "required|email",
            "guest.job"   =>  "string",
            "guest.find_out"    =>  "string",
            "phone" =>  "digits_between:6,17",
            "note"  =>  "string|max:200",
            "guest_list"    =>  "array",
                "guest_list.*"  =>  "string"
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros no admitidos");
    }
}
