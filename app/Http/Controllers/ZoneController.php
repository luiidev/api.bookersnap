<?php

namespace App\Http\Controllers;
use App\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * mostrar todas las zonas y sus mesas.
     * @param micrositio_id
     * @return todas las zonas
     */
    public function index()
    {
        $Zone  = Zone::all();
        return response()->json($Zone);
    }
  
    /**
     * listar zona id y sus mesas.
     * @param micrositio_id
     * @return una zona y sus mesas
     */
    public function getZone($id){
  
        $Zone  = Zone::find($id);
  
        return response()->json($Zone);
    }
  
    public function createZone(Request $request){
  
        $Zone = Zone::create($request->all());
  
        return response()->json($Zone);
  
    }
  
    public function deleteZone($id){

        $Zone  = Zone::find($id);

        $Zone->delete();
 
        return response()->json('deleted');
    }
  
    public function updateZone(Request $request,$id){
        $Zone  = Zone::find($id);
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
}
