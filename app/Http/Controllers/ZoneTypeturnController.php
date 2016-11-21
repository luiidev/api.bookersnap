<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ZoneTypeturnService;

class ZoneTypeturnController extends Controller {

    protected $_ZoneTypeturnService;
    
    public function __construct(ZoneTypeturnService $ZoneTypeTurnDayService) {
        $this->_ZoneTypeturnService = $ZoneTypeTurnDayService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($lang, int $microsite_id, int $zone_id, int $id) {
        $result = $this->_ZoneTypeturnService->getList($zone_id, $id);        
        return response()->json($result);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function available($lang, int $microsite_id, int $zone_id, int $id) {
        $result = $this->_ZoneTypeturnService->getListAvailable($zone_id, $id);        
        return response()->json($result);
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
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
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        //
    }

}
