<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Controllers\Controller as Controller;
use App\Services\NoteService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    protected $_NoteService;

    public function __construct(NoteService $NoteService)
    {
        $this->_NoteService = $NoteService;
    }

    public function index(Request $request)
    {
        return $this->TryCatchDB(function () use ($request) {
          
            $microsite_id = $request->route('microsite_id');
            $date = $request->input('date');            
            $note = $this->_NoteService->getList($request->route('microsite_id'), $date);
            return $this->CreateJsonResponse(true, 201, "Listado de notas", $note);
        });
    }

    public function create(Request $request)
    {
        $service = $this->_NoteService;
        return $this->TryCatchDB(function () use ($request, $service) {
            
            $microsite_id = $request->route('microsite_id');           
            $date = $request->input('date');
            
            $note = $service->saveNote($request->all(), $microsite_id, $date);

            event(new EmitNotification("b-mesas-floor-notes",
                array(
                    'microsite_id' => $request->route('microsite_id'),
                    'data' => $note,
                    'key' => $request->key
                )
            ));

            return $this->CreateJsonResponse(true, 201, "Se agrego una nueva nota", $note);
        });
    }

}
