<?php

namespace App\Http\Controllers;

use App\Services\NoteService;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    protected $_NoteService;

    public function __construct(NoteService $NoteService)
    {
        $this->_NoteService = $NoteService;
    }

    public function create(Request $request)
    {
        return $this->TryCatchDB(function () use ($request) {
            $note = $this->_NoteService->create($request->all(), $request->route('microsite_id'));
            return $this->CreateJsonResponse(true, 201, "Se agrego una nueva nota", $note);
        });
    }

}
