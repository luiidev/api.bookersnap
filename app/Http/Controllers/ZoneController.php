<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\res_zone;
use App\res_table;
use App\Services\ZoneService;
use App\Http\Requests\ZoneRequest;

class ZoneController extends Controller {

    protected $_ZoneService;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ZoneService $ZoneService) {
        $this->_ZoneService = $ZoneService;
    }

    /**
     * mostrar todas las zonas y sus mesas.
     * @param micrositio_id
     * @return todas las zonas
     */
    public function index($lang, int $microsite_id) {

        $result = $this->_ZoneService->getList($microsite_id);
        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($lang, int $microsite_id, int $zone_id) {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ZoneRequest $request, $lang, int $microsite_id) {
        $result = $this->_ZoneService->create($request->all(), $microsite_id);
        return response()->json($result);
    }

    /**
     * Display the specified resource.
     *
     * @param int   $micrositio_id
     * @param int   $id
     * @return \Illuminate\Http\Response
     */
    public function show($lang, int $microsite_id, int $id) {
        
        $result = $this->_ZoneService->get($microsite_id, $id);
        return response()->json($result);
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $lang, int $microsite_id, int $id) {
        $Zone = res_zone::find($id);
        $Zone->name = $request->input('name');
        $Zone->sketch = $request->input('sketch');
        $Zone->status = $request->input('status');
        $Zone->type_zone = $request->input('type_zone');
        $Zone->join_table = $request->input('join_table');
        $Zone->status_smoker = $request->input('status_smoker');
        $Zone->people_standing = $request->input('people_standing');
        $Zone->user_add = $request->input('user_add');
        $Zone->user_upd = $request->input('user_upd');
        $Zone->ev_event_id = $request->input('ev_event_id');
        $Zone->ms_microsite_id = $request->input('ms_microsite_id');
        $Zone->save();
        return response()->json($Zone);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($lang, int $microsite_id, int $id) {
        $Zone = res_zone::find($id);
        $Zone->delete();
        return response()->json('deleted');
    }

}
