<?php

namespace App\Http\Controllers;
use App\Zone;
use App\Table;
use Illuminate\Http\Request;

class TableController extends Controller
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
    public function index($id)
    {
        $Table  = Table::where('res_zone_id',$id);
        return response()->json($Table);
    }
  
    /**
     * listar zona id y sus mesas.
     * @param micrositio_id
     * @return una zona y sus mesas
     */
    public function getTable($tid){
  
        $Table  = Table::find($tid);
  
        return response()->json($Table);
    }
  
    public function createTable(Request $request){
  
        $Table = Table::create($request->all());
  
        return response()->json($Table);
  
    }
  
    public function deleteTable($id){

        $Table  = Table::find($id);

        $Table->delete();
 
        return response()->json('deleted');
    }
  
    public function updateTable(Request $request,$id){
        $Table  = Table::find($id);
        $Table->name = $request->input('name');
        $Table->res_zone_id = $request->input('res_zone_id');
        $Table->min_cover = $request->input('min_cover');
        $Table->max_cover = $request->input('max_cover');
        $Table->price = $request->input('price');
        $Table->config_color = $request->input('config_color');
        $Table->config_position = $request->input('config_position');
        $Table->config_rotation = $request->input('config_rotation');
        $Table->config_size = $request->input('config_size');
        $Table->config_forme = $request->input('config_forme');
        $Table->status = $request->input('status');
        $Table->date_add = $request->input('date_add');
        $Table->user_add = $request->input('user_add');
        $Table->user_upd = $request->input('user_upd');

        $Table->save();
  
        return response()->json($Table);
    }
}
