<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Carbon\Carbon;

class AvailabilityInfoRequest extends Request
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
        $dateMin = Carbon::yesterday($this->timezone)->subDay()->toDateString();
        return [
            "date"       => "required|date_format: Y-m-d|after:$dateMin",
            "hour"       => "date_format: H:i:s|multiple_hour:15",
            "next_day"   => "integer|between:0,1",
            "zone_day"   => "integer|exists:res_zone,id",
            "num_guests" => "integer",
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }
}
