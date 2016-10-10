<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Carbon\Carbon;
use App\Services\Traits\ResponseFormatTrait;

class TableReservationRequest extends Request
{
    use ResponseFormatTrait;

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
        $now = Carbon::now()->toDateString();

        return [
            "guest_id"  =>  "exists:res_guest,id",
            "status_id" =>  "required|exists:res_reservation_status,id",
            "covers" =>  "required|integer|between:1,999",
            "date" =>  "required|date|after:$now",
            "hour" =>  "required",
            "duration" =>  "required",
            "server_id" =>  "exists:res_server,id",
            "note" =>  "string",
            "guest" =>  "array",
                "guest.email" => "email",
                "guest.phone" => "digits_between:7,15",
            "tables" =>  "array",
                "tables.*" => "required|integer|exists:res_table,id"
        ];
    }

    public function response(array $errors)
    {
        return  $this->CreateJsonResponse(false, 422, "Parametros no admitidos", $errors);
    }
}
