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
            'hour'       => 'required | date_format: H:i:s',
            'date'       => 'required | date_format: Y-m-d',
            'num_guests' => 'required | integer',
            'next_day'   => 'required | integer',
        ];

    }
    // regex:/^([0-9]{2}:[15]{2}:[0-9]{2})$/'

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "PARAMETROS recibidos no son validos");
    }
}
