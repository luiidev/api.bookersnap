<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class BlockUpdateRequest extends Request {

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
            'start_date' => 'required|max:3000',
            'start_time' => 'required|max:100',
            'end_time' => 'required',
            'tables' => 'required||array',
            'tables.*.id' => 'required||integer',
        ];
    }

}