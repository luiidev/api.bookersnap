<?php

namespace App\Http\Controllers;
use App\Services\ServerService;
use App\Http\Requests\ServerCreateRequest;
use App\Http\Requests\ServerUpdateRequest;
//use App\Http\Requests\BlockListRequest;
//use App\Http\Requests\BlockUpdateRequest;
use App\Http\Controllers\Controller as Controller;

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
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });

    }

    public function update($lang, $microsite, $server_id, ServerUpdateRequest $request){
        
        return $this->TryCatch(function () use ($microsite, $server_id, $request) {
            $data = $this->_serverService->update($microsite, $server_id, $request->all());
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
        
    }

	public function listado($lang, $microsite){
        return $this->TryCatch(function () use ($microsite) {
            $data = $this->_serverService->listado($microsite);
            return $this->CreateJsonResponse(true, 201, "messages.server_list",$data);
        });
	}

}
