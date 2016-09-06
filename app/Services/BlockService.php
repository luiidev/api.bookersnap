<?php

namespace App\Services;

use App\Entities\Block;
use App\Entities\Table;
use App\Entities\BlockTable;
Use DB;
Use Exception;

class BlockService {	


    public function listado($microsite) {

        	$data = array();
        	$blocks = Block::where("ms_microsite_id","=",$microsite)->get();
        	$m=0;
        	foreach ($blocks as $block) {
        		//dd($block);

        		$data[$m]["id"] = $block->id;
        		$data[$m]["start_date"] = $block->start_date;
        		$data[$m]["start_time"] = $block->start_time;
        		$data[$m]["end_time"] = $block->end_time;
        		$data[$m]["tables"] = array();

        		$blockTables= BlockTable::where("res_block_id", "=", $block->id)->get();
	    
        		$i=0;
        		foreach ($blockTables as $item) {
        			$data[$m]["tables"][$i]["id"] = $item->res_table_id;
        			$i++;
        		}
        		
        		$m++;
        	}

             return $data;

    }

    public function insert($microsite, $data) {


    	DB::beginTransaction();
        try {

	        $model = new Block();
	        $model->start_date = isset($data["start_date"]) ? $data["start_date"] : "";
	        $model->start_time = isset($data["start_time"]) ? $data["start_time"] : "";
	        $model->end_time = isset($data["end_time"]) ? $data["end_time"] : "";
	        $model->ms_microsite_id = $microsite;

	        $model->user_add = 1;
	        $model->date_add = date("Y-m-d H:i:s");

            if (!$model->save()) {
                throw new Exception('messages.block_error_save_turn');
            }


            foreach ($data["tables"] as $table) {
            	$blockTable = new BlockTable();
            	$blockTable->res_block_id = $model->id;
            	$blockTable->res_table_id = $table["id"];
            	if (!$blockTable->save()) {
                	throw new Exception('messages.block_error_save_turn');
            	}
            }
            DB::commit();

            $response["mensaje"] = "messages.block_create_success";
            $response["estado"] = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }
        return (object) $response;

    }

    public function update($microsite, $block_id, $data) {


    	DB::beginTransaction();
        try {

	        $model = Block::find($block_id);
	        //dd($model);
	        if ($model == NULL) {
                throw new Exception('messages.block_not_exist_turn');
            }

	        $model->start_date = isset($data["start_date"]) ? $data["start_date"] : $model->start_date;
	        $model->start_time = isset($data["start_time"]) ? $data["start_time"] : $model->start_time;
	        $model->end_time = isset($data["end_time"]) ? $data["end_time"] : $model->end_time;
	        $model->user_upd = 1;
	        $model->date_upd = date("Y-m-d H:i:s");

            if (!$model->update()) {
                throw new Exception('messages.block_error_update');
            }

            $blockTable = BlockTable::where("res_block_id","=", $block_id);
            //dd(count($blockTable->get()));
            if(count($blockTable->get())>0){
				if (!$blockTable->delete()) {
                	throw new Exception('messages.block_table_error_delete_turn');
            	}   
            }

            foreach ($data["tables"] as $table) {

            	$blockTable = new BlockTable();
            	$blockTable->res_block_id = $model->id;
            	$blockTable->res_table_id = $table["id"];
            	if (!$blockTable->save()) {
                	throw new Exception('messages.block_error_update');
            	}
            }
            DB::commit();

            $response["mensaje"] = "messages.block_update_success";
            $response["estado"] = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }
        return (object) $response;

    }

    public function delete($microsite, $block_id) {

      	DB::beginTransaction();

        try {

        	$block = Block::find($block_id);
            if ($block == NULL) {
                throw new Exception('messages.block_not_exist_turn');
            }
            if (!$block->delete()) {
                throw new Exception('messages.block_error_update_turn');
            }

            $blockTable = BlockTable::where("res_block_id","=",$block_id);
			if (!$blockTable->delete()) {
                throw new Exception('messages.block_table_error_update_turn');
            }            

            DB::commit();
            $response["mensaje"] = "messages.block_delete_success";
            $response["estado"] = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();

        }	
        return (object) $response;
    }

}
