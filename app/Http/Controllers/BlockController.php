<?php


namespace App\Http\Controllers;
use App\Services\BlockService;
use App\Http\Requests\BlockCreateRequest;
use App\Http\Requests\BlockListRequest;
use App\Http\Requests\BlockUpdateRequest;
use App\Http\Controllers\Controller as Controller;

class BlockController  extends Controller {

    protected $_blockService;

    public function __construct(BlockService $blockService) {
        $this->_blockService = $blockService;
    }

	public function delete($lang, $microsite, $block_id){

		return $this->TryCatch(function () use ($microsite, $block_id) {
            $data = $this->_blockService->delete($microsite, $block_id);
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
	}

    public function insert($lang, $microsite, BlockCreateRequest $request){

        return $this->TryCatch(function () use ($microsite, $request) {
            $data = $this->_blockService->insert($microsite, $request->all());
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });

    }
    
    public function getBlock($lang, $microsite, $id_block){

        return $this->TryCatch(function () use ($microsite, $id_block) {
            $data = $this->_blockService->getBlock($microsite, $id_block);
            return $this->CreateJsonResponse(true, 201, 'messages.block_list', $data);
        });

    }

	public function list($lang, $microsite, BlockListRequest $request){
        return $this->TryCatch(function () use ($microsite, $request) {
            $data = $this->_blockService->listado($microsite, $request->all());
            return $this->CreateJsonResponse(true, 201, "messages.block_list",$data);
        });
	
	}


    public function getTables($lang, $microsite, BlockListRequest $request){

        return $this->TryCatch(function () use ($microsite, $request) {
            $data = $this->_blockService->getTables($microsite, $request->all());
            return $this->CreateJsonResponse(true, 201, "messages.block_list",$data);
        });
        
    }
    

	public function update($lang, $microsite, $block_id, BlockUpdateRequest $request){

		return $this->TryCatch(function () use ($microsite, $block_id, $request) {
            $data = $this->_blockService->update($microsite, $block_id, $request->all());
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
		
	}
 
}
