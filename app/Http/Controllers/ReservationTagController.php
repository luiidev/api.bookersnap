<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\ReservationTagRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ReservationTagService as Service;

class ReservationTagController extends Controller
{
    private $service;

    function __construct(Request $request)
    {
        $this->service = Service::make($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tags = $this->service->get_tags();
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
        return $this->TryCatchDB(function() {
            $tag = $this->service->create_tag();
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
    public function destroy($id)
    {
        $this->service->destroy_tag();
        return $this->CreateJsonResponse(true, 200, "Se elimino tag seleccionado");
    }
}
