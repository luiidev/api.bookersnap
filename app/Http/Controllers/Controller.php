<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesResources;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Controller extends BaseController
{
    use AuthorizesRequests, AuthorizesResources, DispatchesJobs, ValidatesRequests;

    protected function TryCatch($closure) {
        $response = null;
        try {
            return $closure();
        } catch (HttpException $e) {
            $response = $this->CreateResponse(false, $e->getStatusCode(), null, null, false, null, $e->getMessage(), $e->getMessage() . "\n" . "{$e->getFile()}: {$e->getLine()}");
        } catch (ModelNotFoundException $e) {
            $response = $this->CreateResponse(false, 404, null, null, false, null, 'No se encontró el recurso solicitado.', $e->getMessage() . "\n" . "{$e->getFile()}: {$e->getLine()}");
        } catch (\Exception $e) {
            $response = $this->CreateResponse(false, 500, null, null, false, null, "Ocurrió un error interno", $e->getMessage() . "\n" . "{$e->getFile()}: {$e->getLine()}");
        }
        return response()->json($response, $response['statuscode']);
    }

    protected function CreateResponse($success, $statusCode = 200, $msg = null, $data = null, $redirect = false, $url = null, $errorUserMsg = null, $errorInternalMsg = null, $arrayErrors = null) {
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


}
