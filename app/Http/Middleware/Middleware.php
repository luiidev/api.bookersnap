<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Middleware;

/**
 * Description of Middleware
 *
 * @author DESKTOP-BS01
 */
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class Middleware {
    
    
    /**
     * @param $success
     * @param $statusCode
     * @param null $msg
     * @param null $data
     * @param bool|false $redirect
     * @param null $url
     * @param null $errorUserMsg
     * @param null $errorInternalMsg
     * @param null $arrayErrors
     * @return \Illuminate\Http\JsonResponse
     * Crea la estructura de respuesta.
     */
    private function CreateJsonResponse($success, $statusCode, $msg = null, $data = null, $redirect = false, $url = null, $errorUserMsg = null, $errorInternalMsg = null, $arrayErrors = null) {
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

    protected function TryCatch($closure) {
        try {
            return $closure();
        } catch (TokenExpiredException $e) {
            return $this->CreateJsonResponse(false, $e->getStatusCode(), trans('messages.token_expired'));
        } catch (TokenInvalidException $e) {
            return $this->CreateJsonResponse(false, $e->getStatusCode(), trans('messages.token_invalid'));
        } catch (JWTException $e) {
            return $this->CreateJsonResponse(false, $e->getStatusCode(), trans('messages.token_absent'));
        } catch (HttpException $e) {
            return $this->CreateJsonResponse(false, $e->getStatusCode(), $e->getMessage());
        }
    }
}
