<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class ServerCreateRequest extends Request {

    public function wantsJson() {
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

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros no admitidos");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'name' => 'required',
            'color' => 'required',
            'tables' => 'required|array',
            'tables.*.id' => 'required|int',
        ];
    }

}
