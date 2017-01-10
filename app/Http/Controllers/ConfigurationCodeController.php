<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationCodeRequest;
use App\Services\ConfigurationCodeService;
use Illuminate\Http\Request;

class ConfigurationCodeController extends Controller
{
    protected $service;

    public function __construct(ConfigurationCodeService $ConfigurationCodeService)
    {
        $this->service = $ConfigurationCodeService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // return $request;
        $codes = $this->service->getCode($request->route("microsite_id"));
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
        $service = $this->service;
        return $this->TryCatchDB(function () use ($service, $request) {
            $response = $service->createCode($request->route("microsite_id"), $request->all());
            return $this->CreateJsonResponse(true, 200, "Se agrego el código", $response);
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
    public function update(ConfigurationCodeRequest $request)
    {
        // $service = $this->service;
        // return $this->TryCatchDB(function () use ($service, $request) {
        //     $response = $this->service->updateCode($request->route("microsite_id"), $request->route("codes"), $request->all());
        //     return $this->CreateJsonResponse(true, 200, "Se actualizo el código", $response);
        // });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $service = $this->service;
        return $this->TryCatchDB(function () use ($service, $request) {
            $response = $service->deleteCode($request->route('microsite_id'), $request->route('codes'));
            return $this->CreateJsonResponse(true, 200, "Se elimino el código", $response);
        });
    }
}
