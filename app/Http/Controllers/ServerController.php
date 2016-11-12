<?php

namespace App\Http\Controllers;
use App\Events\EmitNotification;
use App\Http\Controllers\Controller as Controller;
use App\Http\Requests\ServerCreateRequest;
use App\Http\Requests\ServerUpdateRequest;
use App\Services\ServerService;
use Illuminate\Support\Facades\Validator;

class ServerController  extends Controller {

    protected $_serverService;
    public function __construct(ServerService $serverService) {
        $this->_serverService = $serverService;
    }

    public function delete($lang, $microsite, $server_id){
        return $this->TryCatch(function () use ($microsite, $server_id) {
            $data = $this->_serverService->delete($microsite, $server_id);
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
    }

    public function insert($lang, $microsite, ServerCreateRequest $request){

        return $this->TryCatch(function () use ($microsite, $request) {
            $data = $this->_serverService->insert($microsite, $request->all());

            $this->_notification($microsite, $data->data, "Se añadio un nuevo servidor", "create", $request->key);

            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje), $data->data);
        });

    }

    public function update($lang, $microsite, $server_id, ServerUpdateRequest $request){
        
        $rules = [
            "tables" => "array",
                "table.id" => "exists:res_table,id",
            "id" => "exists:res_server"
        ];

        $request->id = $server_id;

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->CreateJsonResponse(true, 422, null, null, null, null, "No se enontro el servidor.");
        }

        return $this->TryCatch(function () use ($microsite, $server_id, $request) {
            $data = $this->_serverService->update($microsite, $server_id, $request->all());

            $this->_notification($microsite, [$data->server], "Actualizacion de servidor y mesas", "update", $request->key);

            return $this->CreateJsonResponse($data->estado, 200, trans($data->mensaje), [$data->server]);
        });
        
    }

    public function listado($lang, $microsite){

        return $this->TryCatch(function () use ($microsite) {
            $data = $this->_serverService->listado($microsite);
            return $this->CreateJsonResponse(true, 201, "messages.server_list",$data);
        });
        
    }

    private function _notification(Int $microsite_id, $data, String $message, String $action, String $key = null)
    {
        event(new EmitNotification("b-mesas-floor-server",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $data,
                'action'       => $action,
                'key'          => $key,
            )
        ));
    }
}
