<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Carbon\Carbon;

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
        // $dateMin = Carbon::yesterday($this->timezone)->toDateString();
        // $dateMax = Carbon::tomorrow($this->timezone)->toDateString();

        $dateMin = Carbon::yesterday($this->timezone)->subDay()->toDateString();
        // $dateMax = Carbon::tomorrow('America/Lima')->toDateString();

        return [
            'hour'       => 'required|date_format: H:i:s|multiple_hour:15',
            // 'date'       => "required|date_format: Y-m-d|after:$dateMin|before:$dateMax",
            'date'       => "required|date_format: Y-m-d|after:$dateMin",
            'num_guests' => 'required|integer',
            'zone_id'    => 'integer|exists:res_zone,id,ms_microsite_id,'.$this->route('microsite_id'),
            'event_id'   => 'integer|exists:ev_event,id,ms_microsite_id,'.$this->route('microsite_id'),
        ];

    }
    // regex:/^([0-9]{2}:[15]{2}:[0-9]{2})$/'

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }
}
// Validator::extend('mutiple_date', function ($attribute, $value, $parameters) {
//     list($y, $m, $d) = split("-", $value);
//     $resto           = fmod((int) $m, 15);
//     if ($resto == 0) {
//         return true;
//     } else {
//         return false;
//     }

// });
