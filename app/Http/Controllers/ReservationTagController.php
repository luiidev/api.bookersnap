<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationTagRequest;
use App\Services\ReservationTagService as Service;
use Illuminate\Http\Request;

class ReservationTagController extends Controller
{
    private $service;

    public function __construct(Request $request)
    {
        $this->service = Service::make($request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($lang, $microsite_id)
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
    public function store(ReservationTagRequest $request, $lang, $microsite_id)
    {
        // dd("TEST");
        return $this->TryCatchDB(function () {
            $response = $this->service->create_tag();
            // dd($response);
            return $this->CreateJsonResponse(true, 200, "Se agrego nuevo tag", $response);
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
        return "=)";
        $response = $this->service->destroy_tag();
        return $this->CreateJsonResponse(true, 200, "Se elimino tag seleccionado", $response);
    }
}
