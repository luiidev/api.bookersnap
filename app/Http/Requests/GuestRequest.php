<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Requests;

/**
 * Description of ConfigZoneRequest
 *
 * @author USER
 */
use App\Http\Requests\Request;

class GuestRequest extends Request
{

    public function wantsJson()
    {
        return true;
    }
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
            "first_name"      => "required|string",
            "last_name"       => "string",
            "birhtdate"       => 'date_format:Y-m-d',
            "gender"          => 'string|in:M,F,O',
            "emails.*.id"     => "integer",
            "emails.*.email"  => "string|email",
            "phones.*.id"     => 'integer',
            "phones.*.number" => 'string',
            "tags.*.id"       => 'integer',

        ];
    }

    public function messages()
    {
        return [

        ];
    }

}
