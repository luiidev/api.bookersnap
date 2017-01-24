<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\Traits\ResponseFormatTrait;
use Illuminate\Contracts\Validation\Validator;

abstract class Request extends FormRequest {

    use ResponseFormatTrait;

    public function all() {
        /*
         * Fixes an issue with FormRequest-based requests not
         * containing parameters added / modified by middleware
         * due to the FormRequest copying Request parameters
         * before the middleware is run.
         *
         * See:
         * https://github.com/laravel/framework/issues/10791
         */
        $this->merge($this->request->all());
        return parent::all();
    }
    
    public function response(array $errors)
    {
        return $this->CreateJsonResponseValidation(false, 422, "", $errors, null, null, "Los datos enviados son incorrectos.");
    }
    
    protected function formatErrors(Validator $validator)
    {
        return $this->JsonResponse(parent::formatErrors($validator));
    }

    private function JsonResponse($errors)
    {
        return [
            "success" => false,
            "statuscode" => 422,
            "msg" => 'Ocurrieron errores al validar los datos.',
            "data" => null,
            "redirect" => false,
            "url" => null,
            "error" => [
                "user_msg" => 'Verifique las siguientes inconsistencias.',
                "internal_msg" => null,
                "errors" => $errors
            ]
        ];
        
    }

}
