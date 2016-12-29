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
            "guest.first_name"    =>  "required|alpha_spaces|",
            "guest.last_name" =>  "required|alpha_spaces",
            "guest.birthdate" =>  "required|date",
            "guest.email" =>  "required|email",
            "guest.phone" =>  "digits_between:6,17",
            "guest.profession"   =>  "alpha_spaces",
            "guest.find_out"    =>  "string",
            "note"  =>  "string|max:200",
            "guest_list"    =>  "array",
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros no admitidos");
    }

    public function messages() {
            return [
                // "token.required" => "El campo token es requerido",
                // "token.exists" => "El token no es valido",
                // "guest.required" => "Los datos del invitado es requerido",
                // "guest.first_name.required" => "El campo nombre es requerido",
                "guest.first_name.alpha_spaces" => "El campo nombre solo puede contener letras.",
                // "guest.last_name.required" => "El campo apellido es requerido",
                "guest.last_name.alpha_spaces" => "El campo apellido solo puede contener letras.",
                // "guest.birthdate.required" => "El campo cumpleaños es requerido",
                // "guest.birthdate.date" => "El campo cumpleaños no es una fecha valida.",
                // "guest.email.required" => "El campo correo es requerido",
                // "guest.phone.digits_between" => "El campo de telefono debe contener entre 6 y 17 caracteres",
                "guest.profession.alpha_spaces" => "El campo profesion solo puede contener letras.",
                // "guest.find_out.string" => "El campo debe contener solo caracteres",
                // "note.string" => "El campo nota contener solo caracteres",
                // "note.max" => "El campo nota debe contener una maximo de 200 caracteres",
                "guest_list.array" => "El campo debe ser una lista"
            ];
    }
}
