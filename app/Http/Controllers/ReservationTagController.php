<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Requests\ReservationTagRequest;
use App\res_tag_r;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReservationTagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($lang, $microsite_id)
    {
        $tags = res_tag_r::where("ms_microsite_id", $microsite_id)->get(array("id", "name", "status"));
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
        $request["ms_microsite_id"] = $microsite_id;
        res_tag_r::create($request->all());

        return $this->CreateJsonResponse(true, 201, "Se agrego nuevo tag");
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
    public function destroy($lang, $microsite_id, $id)
    {
        res_tag_r::destroy($id);

        return $this->CreateJsonResponse(true, 200, "Se elimino tag seleccionado");
    }
}
