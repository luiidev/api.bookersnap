<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationRequest;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    private $service;

    public function __construct(ConfigurationService $ConfigurationService)
    {
        $this->service = $ConfigurationService;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $microsite_id = $request->route("microsite_id");
        $configs      = $this->service->getConfiguration($microsite_id);
        if ($configs != null) {
            return $this->CreateJsonResponse(true, 200, "", $configs);
        } else {
            return $this->TryCatchDB(function () use ($microsite_id) {
                $response = $this->service->createDefaultConfiguration($microsite_id);
                return $this->CreateJsonResponse(true, 200, "Se agrego configuración inicial", $response);
            });
        }
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
    public function store(ConfigurationRequest $request)
    {

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
    public function edit(ConfigurationRequest $request)
    {
        $microsite_id = $request->route("microsite_id");
        return $this->TryCatchDB(function () use ($microsite_id, $request) {
            $response = $this->service->updateCodeStatus($microsite_id, $request->all());
            return $this->CreateJsonResponse(true, 200, "Se actualizo el código", $response);
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ConfigurationRequest $request)
    {
        // return $request->all();
        // return $request->route("microsite_id");
        $microsite_id = $request->route("microsite_id");
        return $this->TryCatchDB(function () use ($microsite_id, $request) {
            $response = $this->service->updateConfiguration($microsite_id, $request->all());
            return $this->CreateJsonResponse(true, 200, "Se actualizo la configuración", $response);
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
        //
    }
}
