<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class GuestListRequest extends Request
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
            "guest_list"    =>  "array",
                "guest_list.*.id"    =>  "required|exists:res_reservation_guestlist,id",
                "guest_list.*.arrived"   =>  "required|integer|between: 0,1",
                "guest_list.*.type_person"   =>  "integer|between: 1,3",
            "guest_list_add"    =>  "array",
                "guest_list_add.*.name"    =>  "required|string|max:128",
                "guest_list_add.*.arrived"   =>  "required|integer|between: 0,1",
                "guest_list_add.*.type_person"   =>  "integer|between: 1,3"
        ];
    }

    public function response(array $errors)
    {
        return $this->CreateJsonResponse(false, 422, "", $errors, null, null, "Parametros no admitidos");
    }
}
