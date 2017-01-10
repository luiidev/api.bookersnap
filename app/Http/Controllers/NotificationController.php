<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Services\NotificationService as Service;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private $service;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () {
            $data = $this->service->index();

            return $this->CreateJsonResponse(true, 200, "", $data);
        });
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatchDB(function () {
            $this->service->update();

            return $this->CreateJsonResponse(true, 200);
        });
    }

}
