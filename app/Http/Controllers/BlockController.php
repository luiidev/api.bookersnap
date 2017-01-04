<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Controllers\Controller as Controller;
use App\Http\Requests\BlockCreateRequest;
use App\Http\Requests\BlockListRequest;
use App\Http\Requests\BlockUpdateRequest;
use App\Services\BlockService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BlockController extends Controller
{

    protected $_blockService;

    public function __construct(BlockService $blockService)
    {
        $this->_blockService = $blockService;
    }

    public function index(BlockListRequest $request)
    {
        return $this->TryCatch(function () use ($request) {
            $date = $request->input('date');
            $data = $this->_blockService->listado($request->route('microsite_id'), $date);
            return $this->CreateJsonResponse(true, 201, "messages.block_list", $data);
        });
    }

    public function delete(Request $request)
    {
        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->delete($request->route('microsite_id'), $request->route('block_id'));

            $this->_notificationBlock($request->route('microsite_id'), $data->block, "Se elimino un bloqueo", "delete");

            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
    }

    public function insert(BlockCreateRequest $request)
    {

        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->insert($request->route('microsite_id'), $request->all());

            $this->_notificationBlock($request->route('microsite_id'), $data->block_id, "Se agrego un nuevo bloqueo", "create");

            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });

    }

    public function getBlock(Request $request)
    {

        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->getBlock($request->route('microsite_id'), $request->route('block_id'));
            return $this->CreateJsonResponse(true, 201, 'messages.block_list', $data);
        });

    }

    public function getTables(BlockListRequest $request)
    {
        return $this->TryCatch(function () use ($request) {
            $dateNow = Carbon::now();
            $date    = $request->input('date', $dateNow->format('Y-m-d'));
            $data    = $this->_blockService->getTables($request->route('microsite_id'), $date);
            return $this->CreateJsonResponse(true, 201, "messages.block_list", $data);
        });

    }

    public function update(BlockUpdateRequest $request)
    {
        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->update($request->route('microsite_id'), $request->route('block_id'), $request->all());
            $this->_notificationBlock($request->route('microsite_id'), $request->route('block_id'), "Se edito un bloqueo", "update");
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
    }

    public function updateGrid(Request $request)
    {
        return $this->TryCatchDB(function () use ($request) {
            $response = $this->_blockService->updateByGrid($request->all(), $request->route('microsite_id'));

            $action = (count($request->input('tables_deleted')) > 0) ? "patch" : "update";

            $this->_notificationBlock($request->route('microsite_id'), $request->route('block_id'), "Se edito un bloqueo", $action);
            return $this->CreateJsonResponse($response, 201, "Bloqueo actualizado");
        });

    }

    private function _notificationBlock(Int $microsite_id, $block, $message, String $action)
    {
        if ($action == "update") {
            $data      = $this->_blockService->getBlock($microsite_id, $block);
            $blockData = array($data);
        } else if ($action == "delete") {
            $blockData = $block;
        } else {
            $data      = $this->_blockService->getBlock($microsite_id, $block);
            $blockData = array($data);
        }
        event(new EmitNotification("b-mesas-floor-upd-block",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => $message,
                'data'         => $blockData,
                'action'       => $action,
            )
        ));
    }

}
