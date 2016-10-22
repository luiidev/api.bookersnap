<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class AvailabilityRequest extends Request
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
            'hour'       => 'date_format: H:i:s',
            'date'       => 'date_format: Y-m-d',
            'num_guests' => 'integer',
        ];

    }
    // regex:/^([0-9]{2}:[15]{2}:[0-9]{2})$/'

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }
}
