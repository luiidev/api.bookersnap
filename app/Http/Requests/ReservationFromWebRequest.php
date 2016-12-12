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
            "guest.first_name"    =>  "required|alpha|",
            "guest.last_name" =>  "required|alpha",
            "guest.email" =>  "required|email",
            "guest.job"   =>  "alpha",
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

    public function messages() {
            return [
                // "token.required" => "El campo token es requerido",
                // "token.exists" => "El token no es valido",
                // "guest.required" => "Los datos del invitado es requerido",
                // "guest.first_name.required" => "El campo nombre es requerido",
                // "guest.first_name.alpha" => "El campo nombre solo puede contener letras",
                // "guest.last_name.required" => "El campo apellido es requerido",
                // "guest.last_name.alpha" => "El campo apellido solo puede contener letras",
                // "guest.email.required" => "El campo correo es requerido",
                // "guest.job.alpha" => "El campo profesion solo puede contener letras",
                // "guest.find_out.string" => "El campo debe contener solo caracteres",
                // "phone.digits_between" => "El campo de telefono debe contener entre 6 y 17 caracteres",
                // "note.string" => "El campo nota contener solo caracteres",
                // "note.max" => "El campo nota debe contener una maximo de 200 caracteres",
                // "guest_list.*.string" => "La lista de invitados debe contener solo caracteres"
            ];
    }
}
