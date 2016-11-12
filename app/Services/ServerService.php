<?php

namespace App\Services;

//use App\Helpers\Utilitarios;
use App\Entities\Server;
use App\Entities\Table;
use App\res_server;
use App\res_table;
Use DB;
Use Exception;

class ServerService {	

    public function listado($microsite) {
        
        $servers = Server::where("ms_microsite_id", "=", $microsite)->get();
        $i=0;
        $data = array();
        foreach ($servers as $server) {

            $data[$i]["id"] = $server->id;
            $data[$i]["name"] = $server->name;
            $data[$i]["color"] = $server->color;
            $data[$i]["tables"] = array();

            $tables = Table::where("res_server_id","=", $server->id)->get();
            $m=0;
            foreach ($tables as $table) {
                $data[$i]["tables"][$m]["id"] = $table->id;
                $data[$i]["tables"][$m]["name"] = $table->name;
                $m++;
            }
            $i++;
        }

        return $data;

    }

    public function insert($microsite, $variables) {

        DB::beginTransaction();
        try {
            /**
             * Tablas afectadas
             */
            $tables_id = collect($variables["tables"])->pluck("id");
            $filter = res_table::select("res_server_id")->distinct()->whereIn("id", $tables_id)->get()->pluck("res_server_id");
            /**
             * END
             */

            $tables = array();

            $model = new Server();
            $model->name = $variables["name"];
            $model->color = $variables["color"];
            $model->date_add = date("Y-m-d H:i:s");
            $model->user_add = 1;
            $model->ms_microsite_id = $microsite;

            if (!$model->save()) {
                throw new Exception('messages.server_error_save');
            }
            $i=0;
            foreach ($variables["tables"] as $table) {
                $modelTable = Table::find($table["id"]);
                if($modelTable == NULL){
                    throw new Exception('messages.table_update_not_exist');
                }else{
                    $modelTable->res_server_id = $model->id;
                    $modelTable->date_upd = date("Y-m-d H:i:s");
                    $modelTable->user_upd = 1;
                    if(!$modelTable->update()){
                      throw new Exception('messages.table_error_update');  
                    }

                    $tables[$i]["id"] = $modelTable->id;
                    $tables[$i]["name"] = $modelTable->name;

                }
                $i++;
            }
            DB::commit();

            /**
             * Tablas afectadas
             */
            $others = res_server::with(["tables" => function($query) {
                return $query->select("id", "name", "res_server_id");
            }])->where("id", "<>", $model->id)->whereIn("id", $filter)->get();
            /**
             * END
             */

            $data["id"] = $model->id;
            $data["name"] = $model->name;
            $data["color"] = $model->color;
            $data["tables"] = $tables;

            $response["mensaje"] = "messages.server_create_success";
            $response["estado"] = true;
            $response["server"] = $data;
            $response["others"] = $others;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            $response["data"] = array();
            DB::rollBack();
        }



        return (object) $response;

    }

    public function update($microsite, $server_id, $variables) {

        DB::beginTransaction();
        try {

            $server = res_server::where("ms_microsite_id", $microsite)->where("id", $server_id)->first();
            $server->name = $variables["name"];
            $server->color = $variables["color"];
            $server->user_upd = 1;

            $server->save();

            res_table::where("res_server_id", $server_id)->update(["res_server_id" => null]);

            $tables = collect($variables["tables"])->pluck("id");

            $filter = res_table::select("res_server_id")->distinct()->whereIn("id", $tables)->get()->pluck("res_server_id");
            res_table::whereIn("id", $tables)->update(["res_server_id" => $server_id, "user_upd" => 1]);

            $data = res_server::with(["tables" => function($query) {
                return $query->select("id", "name", "res_server_id");
            }])->where("id", $server_id)->orWhereIn("id", $filter)->get();

            DB::commit();

            $response["mensaje"] = "messages.server_update_success";
            $response["estado"] = true;
            $response["servers"]  = $data;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }

        return (object) $response;

    }

    public function delete($microsite, $server_id) {

        DB::beginTransaction();

        try {

            $data = res_server::with(["tables" => function($query) {
                return $query->select("id", "name", "res_server_id");
            }])->find($server_id);

            $server = Server::find($server_id);
            if ($server == NULL) {
                throw new Exception('messages.server_not_exist_turn');
            }
            if (!$server->delete()) {
                throw new Exception('messages.server_error_delete_tables');
            }

            $tables = DB::table('res_table')->where('res_server_id', '=', $server_id)->update(array('res_server_id' => NULL));

            DB::commit();

            $response["mensaje"] = "messages.server_delete_success";
            $response["estado"] = true;
            $response["server"]  = $data;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            $response["server"]  = null;
            DB::rollBack();

        }   
        return (object) $response;
    }

}
