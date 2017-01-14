<?php

namespace App\Services\Traits;

trait ResponseFormatTrait
{
     protected function CreateJsonResponse($success, $statusCode, $msg = null, $data = null, $redirect = false, $url = null, $errorUserMsg = null, $errorInternalMsg = null, $arrayErrors = null) {
        $response = [
            "success" => $success,
            "statuscode" => $statusCode,
            "msg" => $msg,
            "data" => $data,
            "redirect" => $redirect,
            "url" => $url,
            "error" => [
                "user_msg" => $errorUserMsg,
                "internal_msg" => $errorInternalMsg,
                "errors" => $arrayErrors
            ]
        ];

        return response()->json($response, $statusCode);
    }
    
    protected function CreateJsonResponseValidation($success, $statusCode, $msg = null, $data = null, $redirect = false, $url = null, $errorUserMsg = null, $errorInternalMsg = null, $arrayErrors = null) {
        $response = [
            "success" => $success,
            "statuscode" => $statusCode,
            "msg" => $msg,
            "data" => $data,
            "redirect" => $redirect,
            "url" => $url,
            "error" => [
                "user_msg" => $errorUserMsg,
                "internal_msg" => $errorInternalMsg,
                "errors" => $arrayErrors
            ]
        ];

        return response()->json($response, $statusCode);
    }
    
    private function formatErrorValidation($data){
        
        $dataError = [];
        
        
        
        foreach ($data as $key => $value) {
            
            $index = strpos($key, ".");
            if(!$index){
                $dataError[$key] = $value;               
            }else{         
                $subkey = substr($key, 0, $index-1);
                $poskey = substr($key, $index);
                $subval = substr($key, $index);
                $dataError[$subkey] = $subval;
            }
            
        }
        return $dataError;
    }
    
    public function generateArrayError($data) {
        foreach ($data as $key => $value) {            
            $index = strpos($key, ".");
            if(!$index){
                $dataError[$key] = $value;               
            }else{
                
            }
        }
    }
}