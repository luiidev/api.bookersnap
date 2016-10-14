<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationCodeRequest;
use App\Services\ConfigurationCodeService as Service;
use Illuminate\Http\Request;

class ConfigurationCodeController extends Controller
{
    private $service;

    public function __constructor(Request $request)
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
        $codes = $this->service->getCode();
        return $this->CreateJsonResponse(true, 200, "", $codes);
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
    public function store(ConfigurationCodeRequest $request)
    {
        return $this->TryCatchDB(function () {
            $response = $this->service->createCode();
            return $this->CreateJsonResponse(true, 200, "Se agrego el código", $response);
        })
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
    public function update(ConfigurationCodeRequest $request)
    {
        return $this->TryCatchDB(function () {
            $response = $this->service->updateCode();
            return $this->CreateJsonResponse(true, 200, "Se actualizo el código", $response);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        return $this->TryCatchDB(function () {
            response = $this->service->deleteCode();
            return $this->CreateJsonResponse(true, 200, "Se elimino el código", $response);
        });
    }
}
