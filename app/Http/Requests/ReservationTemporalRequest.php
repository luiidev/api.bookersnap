<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Carbon\Carbon;

class ReservationTemporalRequest extends Request
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
        $dateMin = Carbon::yesterday('America/Lima')->toDateString();
        return [
            'hour'        => 'required|date_format: H:i:s|multiple_hour:15',
            'date'        => "required|date_format: Y-m-d|after:$dateMin",
            'num_guest'   => 'required|integer',
            'zone_id'     => 'required|integer|exists:res_zone,id',
            // 'tables_id'   => 'required|string',
            'ev_event_id' => 'integer',
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros recibidos no son validos");
    }
}
