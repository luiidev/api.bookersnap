<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BlockListRequest extends Request {

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

    public function formatErrors(\Illuminate\Contracts\Validation\Validator $validator) {
        $data["response"] = false;
        $data["jsonError"] = $validator->errors()->all();
        return $data;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
        ];
    }

}