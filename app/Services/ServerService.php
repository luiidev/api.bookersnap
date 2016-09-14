<?php

namespace App\Services;

//use App\Helpers\Utilitarios;
use App\Entities\Server;
use App\Entities\Table;
Use DB;
Use Exception;

class ServerService {	

    public function listado($microsite) {

        $servers = Server::where("ms_microsite_id", "=", $microsite)->get();
        $i=0;
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

            $model = new Server();
            $model->name = $variables["name"];
            $model->color = $variables["color"];
            $model->date_add = date("Y-m-d H:i:s");
            $model->user_add = 1;
            $model->ms_microsite_id = $microsite;

            if (!$model->save()) {
                throw new Exception('messages.server_error_save');
            }

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
                }
                

            }
            DB::commit();

            $response["mensaje"] = "messages.server_create_success";
            $response["estado"] = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }

        return (object) $response;

    }

    public function update($microsite, $server_id, $variables) {

        DB::beginTransaction();
        try {

            $model = Server::find($server_id);
            $model->name = $variables["name"];
            $model->color = $variables["color"];
            $model->date_upd = date("Y-m-d H:i:s");
            $model->user_upd = 1;
            if (!$model->update()) {
                throw new Exception('messages.server_error_update');
            }

            $tables = DB::table('res_table')->where('res_server_id', '=', $server_id)->update(array('res_server_id' => NULL));
            if(!$tables){
                throw new Exception('messages.server_update_error');   
            }

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
                }

            }
            
            DB::commit();

            $response["mensaje"] = "messages.server_update_success";
            $response["estado"] = true;

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

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();

        }   
        return (object) $response;
    }

}
