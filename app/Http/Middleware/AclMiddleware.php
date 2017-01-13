<?php

namespace App\Http\Middleware;

use App\Entities\bs_acl;
use App\Entities\ms_manager;
use App\Services\Helpers\CheckPrivilegeHelper;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AclMiddleware {

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $action = null) {

        return $this->TryCatch(function () use ($request, $next, $action) {
            
            $request->request->set('_bs_user_id', 1);

            $CheckPrivilege = new CheckPrivilegeHelper();
            
            $privileges =$CheckPrivilege->getPrivileges(21, 1, 2);

            return response()->json($privileges);

            return $next($request);
                    //se obtienen las credenciales de acceso de los headers
                    $user_info = $this->GetUserInfo();
                    $request->request->set('_bs_user_id', $user_info['id']);
                    $ms_mp_id = $request->header('ms-mp-id');
                    $type_admin_id = $request->header('type-admin');
                    $hasAccess = $this->CheckAuth($user_info['id'], $type_admin_id, $ms_mp_id, $action);
                    if (!$hasAccess) {
                        abort(403, trans('messages.forbidden'));
                    }
                    return $next($request);
                });
                
    }

    //-----------------------------------------------------
    // PRIVATE FUNCTIONS
    //-----------------------------------------------------
    /**
     * @param int $bs_user_id : id de usuario
     * @param int $type_admin : id del type admin
     * @param int $ms_mp_id : id del micrositio o microportal
     * @param string $action : accion a realizar (tabla bs_privilege)
     * @return bool
     */
    private function CheckAuth($bs_user_id, $type_admin, $ms_mp_id, $action) {
        //\Log::info("$bs_user_id, $type_admin, $ms_mp_id, $action");
        try {
            $manager = ms_manager::where('bs_user_id', $bs_user_id)
                    ->where('bs_type_admin_id', $type_admin)
                    ->where('ms_mp_id', $ms_mp_id)
                    ->firstOrFail();
            bs_acl::where('bs_role_id', $manager->bs_role_id)
                    ->where('bs_privilege_action_id', $action)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return false;
        }
        return true;
    }

    /**
     * @return mixed
     * Obtiene y desencripta el token. Valida el campo de auditoria
     * y retorna la informacion del token.
     */
    private function GetUserInfo() {
        JWTAuth::parseToken();
        $token = JWTAuth::getToken();
        $user_info = JWTAuth::decode($token)->get();        
        //\Log::info($user_info['aud']. "  " .$this->GenerateAuditToken() );
        if (@$user_info['aud'] != $this->GenerateAuditToken()) {
            abort(406, 'Usuario logueado inválido.');
        }
        return $user_info;
    }

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

    private function TryCatch($closure) {
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

    /**
     * Genera una codigo encriptado en funcion a la ip y el user agent del request.
     * Este codigo debera ser igual al que envia el token, de lo contrario se invalida
     * la solicitud.
     * @return string
     */
    private function GenerateAuditToken() {
        $_client_ip = request()->server('HTTP_CLIENT_IP');
        $_http_x_forwarded = request()->server('HTTP_X_FORWARDED_FOR');
        $_remote_addr = request()->server('REMOTE_ADDR');
        if (!is_null($_client_ip)) {
            $aud = $_client_ip;
        } elseif (!is_null($_http_x_forwarded)) {
            $aud = $_http_x_forwarded;
        } else {
            $aud = $_remote_addr;
        }
        $aud .= request()->server('HTTP_USER_AGENT');
        //$aud .= gethostname();
        return sha1($aud);
    }

}
