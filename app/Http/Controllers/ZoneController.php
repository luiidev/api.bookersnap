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
        
       /* $result = $this->_ZoneService->getList($microsite_id);
        return response()->json($result);*/
        return response()->json(
        array (
          'success' => true,
          'statuscode' => 201,
          'msg' => 'messages.event_list',
          'data' => 
          array (
            0 => 
            array (
              'id' => 1,
              'created_at' => '2016-07-19 16:42:45',
              'updated_at' => '2016-07-19 16:42:45',
              'name' => 'zona10',
              'sketch' => 'asdas322fsd',
              'status' => 2,
              'type_zone' => 1,
              'join_table' => 1,
              'status_smoker' => 0,
              'people_standing' => 0,
              'user_add' => 1,
              'user_upd' => 2,
              'ev_event_id' => 11,
              'ms_microsite_id' => 1,
              'tables' => 
              array (
                0 => 
                array (
                  'id' => 1,
                  'created_at' => '2016-07-19 17:26:02',
                  'updated_at' => '2016-07-19 17:26:02',
                  'res_zone_id' => 1,
                  'name' => '',
                  'min_cover' => 1,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '',
                  'config_position' => '12,20',
                  'config_forme' => 0,
                  'config_size' => 0,
                  'config_rotation' => 0,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 0,
                  'user_upd' => 0,
                  ),
                2 => 
                array (
                  'id' => 3,
                  'created_at' => '2016-07-19 17:26:16',
                  'updated_at' => '2016-07-19 17:26:16',
                  'res_zone_id' => 1,
                  'name' => '',
                  'min_cover' => 1,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '',
                  'config_position' => '',
                  'config_forme' => 0,
                  'config_size' => 0,
                  'config_rotation' => 0,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 0,
                  'user_upd' => 0,
                  ),
                3 => 
                array (
                  'id' => 4,
                  'created_at' => '2016-07-19 18:21:21',
                  'updated_at' => '2016-07-19 18:21:21',
                  'res_zone_id' => 1,
                  'name' => 'table 4',
                  'min_cover' => 5,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '1',
                  'config_position' => '1',
                  'config_forme' => 1,
                  'config_size' => 1,
                  'config_rotation' => 45,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 1,
                  'user_upd' => 1,
                  ),
                ),
              ),
            1 => 
            array (
              'id' => 2,
              'created_at' => '2016-07-19 16:42:45',
              'updated_at' => '2016-07-19 16:42:45',
              'name' => 'zona10',
              'sketch' => 'asdas322fsd',
              'status' => 1,
              'type_zone' => 1,
              'join_table' => 1,
              'status_smoker' => 0,
              'people_standing' => 0,
              'user_add' => 1,
              'user_upd' => 2,
              'ev_event_id' => 11,
              'ms_microsite_id' => 1,
              'tables' => 
              array (
                0 => 
                array (
                  'id' => 1,
                  'created_at' => '2016-07-19 17:26:02',
                  'updated_at' => '2016-07-19 17:26:02',
                  'res_zone_id' => 1,
                  'name' => '',
                  'min_cover' => 1,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '',
                  'config_position' => '',
                  'config_forme' => 0,
                  'config_size' => 0,
                  'config_rotation' => 0,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 0,
                  'user_upd' => 0,
                  ),
                2 => 
                array (
                  'id' => 3,
                  'created_at' => '2016-07-19 17:26:16',
                  'updated_at' => '2016-07-19 17:26:16',
                  'res_zone_id' => 1,
                  'name' => '',
                  'min_cover' => 1,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '',
                  'config_position' => '',
                  'config_forme' => 0,
                  'config_size' => 0,
                  'config_rotation' => 0,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 0,
                  'user_upd' => 0,
                  ),
                3 => 
                array (
                  'id' => 4,
                  'created_at' => '2016-07-19 18:21:21',
                  'updated_at' => '2016-07-19 18:21:21',
                  'res_zone_id' => 1,
                  'name' => 'table 4',
                  'min_cover' => 5,
                  'max_cover' => 0,
                  'price' => 0,
                  'status' => 1,
                  'config_color' => '1',
                  'config_position' => '1',
                  'config_forme' => 1,
                  'config_size' => 1,
                  'config_rotation' => 45,
                  'date_add' => '0000-00-00 00:00:00',
                  'date_upd' => '0000-00-00 00:00:00',
                  'user_add' => 1,
                  'user_upd' => 1,
                  ),
                ),
              ),
            ),
        'redirect' => false,
        'url' => NULL,
        'error' => 
        array (
            'user_msg' => NULL,
            'internal_msg' => NULL,
            'errors' => NULL,
        ),
        )
      );
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
