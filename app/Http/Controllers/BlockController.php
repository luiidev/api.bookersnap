<?php

namespace App\Http\Controllers;

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
            $dateNow = Carbon::now()->setTimezone($request->timezone);
            $date    = $request->input('date', $dateNow->format('Y-m-d'));
            $data    = $this->_blockService->listado($request->route('microsite_id'), $date);
            return $this->CreateJsonResponse(true, 201, "messages.block_list", $data);
        });
    }

    public function delete(Request $request)
    {

        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->delete($request->route('microsite_id'), $request->route('block_id'));
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });
    }

    public function insert(BlockCreateRequest $request)
    {

        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->insert($request->route('microsite_id'), $request->all());
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
            $dateNow = Carbon::now()->setTimezone($request->timezone);
            $date    = $request->input('date', $dateNow->format('Y-m-d'));
            $data    = $this->_blockService->getTables($request->route('microsite_id'), $date);
            return $this->CreateJsonResponse(true, 201, "messages.block_list", $data);
        });

    }

    public function update(BlockUpdateRequest $request)
    {

        return $this->TryCatch(function () use ($request) {
            $data = $this->_blockService->update($request->route('microsite_id'), $request->route('block_id'), $request->all());
            return $this->CreateJsonResponse($data->estado, 201, trans($data->mensaje));
        });

    }

}
