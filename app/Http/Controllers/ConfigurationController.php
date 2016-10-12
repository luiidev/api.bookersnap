<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationRequest;
use App\Services\ConfigurationService as Service;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
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
    public function index()
    {
        $configs = $this->service->getConfiguration();
        if ($configs != null) {
            return $this->CreateJsonResponse(true, 200, "", $configs);
        } else {
            return $this->TryCatchDB(function () {
                $response = $this->service->createDefaultConfiguration();
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
    public function update(ConfigurationRequest $request)
    {
        return $this->TryCatchDB(function () {
            $response = $this->service->updateConfiguration();
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
