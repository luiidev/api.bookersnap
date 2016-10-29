<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;
use Carbon\Carbon;

class TableReservationRequest extends Request
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
        $date = Carbon::yesterday()->setTimezone($this->timezone)->toDateString();
        return [
            "guest_id"         => "exists:res_guest,id",
            "status_id"        => "required|exists:res_reservation_status,id",
            "covers"           => "required|integer|between:1,999",
            "date"             => "required|date|after:$date",
            "hour"             => "required",
            "duration"         => "required",
            "server_id"        => "exists:res_server,id",
            "note"             => "string",
            "guest"            => "array",
            "guest.first_name" => "string|max:255",
            "guest.last_name"  => "string|max:255",
            "guest.email"      => "email",
            "guest.phone"      => "digits_between:7,15",
            "tables"           => "required|array",
            "tables.*"         => "required|integer|exists:res_table,id",
            "tags"             => "array",
            "tags.*"           => "exists:res_tag_r,id",
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros no admitidos");
    }
}
