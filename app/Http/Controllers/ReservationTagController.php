<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationTagRequest;
use App\Services\ReservationTagService;
use Illuminate\Http\Request;

class ReservationTagController extends Controller
{
    private $service;

    public function __construct(ReservationTagService $ReservationTagService)
    {
        $this->service = $ReservationTagService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd($request);
        $microsite_id = $request->route("microsite_id");
        $tags         = $this->service->get_tags($microsite_id);
        return $this->CreateJsonResponse(true, 200, "", $tags);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReservationTagRequest $request)
    {
        $name         = $request->input("name");
        $microsite_id = $request->route("microsite_id");
        return $this->TryCatchDB(function () use ($microsite_id, $name) {
            $tag = $this->service->create_tag($microsite_id, $name);
            return $this->CreateJsonResponse(true, 201, "Se agrego nuevo tag", $tag);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $idTag = $request->route("tag");

        $response = $this->service->destroy_tag($idTag);
        return $this->CreateJsonResponse(true, 200, "Se elimino tag seleccionado", $response);
    }
}
