<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\FormRequest;
use App\Services\ConfigurationFormService as Service;
use Illuminate\Http\Request;

class ConfigurationFormController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->service = Service::make();
        return $this->TryCatch(function () {
            $reservation = $this->service->list();

            return $this->CreateJsonResponse(true, 200, "", $reservation);
        });
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
    public function store(Request $request)
    {
        //
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
     * @return \Illuminate\Http\Response
     */
    public function update(FormRequest $request)
    {
        $this->service = Service::make($request);
        return $this->TryCatch(function () {
            $reservation = $this->service->updateForm();

            return $this->CreateJsonResponse(true, 200, "El formulario fue actualizado.", $reservation);
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
