<?php

namespace App\Services;

use App\Entities\Block;
use App\Entities\BlockTable;
use App\Entities\TableReservation;
use App\Helpers\Utilitarios;
use App\res_reservation;
use DB;
use Exception;

class BlockService
{

    public function getBlock($microsite, $id_block)
    {

        $data  = array();
        $block = Block::find($id_block);

        $data["id"]         = $block->id;
        $data["start_date"] = $block->start_date;
        $data["start_time"] = $block->start_time;
        $data["end_time"]   = $block->end_time;
        $data["tables"]     = array();

        $blockTables = BlockTable::where("res_block_id", "=", $id_block)->get();

        $i = 0;
        foreach ($blockTables as $item) {
            $data["tables"][$i]["id"] = $item->res_table_id;
            $i++;
        }
        return $data;

    }

    public function listado($microsite, $date)
    {
        $data   = array();
        $blocks = Block::where("ms_microsite_id", "=", $microsite)->where("start_date", "=", $date)->get();
        $m      = 0;
        foreach ($blocks as $block) {

            $data[$m]["id"]         = $block->id;
            $data[$m]["start_date"] = $block->start_date;
            $data[$m]["start_time"] = $block->start_time;
            $data[$m]["end_time"]   = $block->end_time;
            $data[$m]["tables"]     = array();

            $blockTables = BlockTable::where("res_block_id", "=", $block->id)->get();

            $i = 0;
            foreach ($blockTables as $item) {
                $data[$m]["tables"][$i]["id"] = $item->res_table_id;
                $i++;
            }

            $m++;
        }
        return $data;

    }

    public function getTables($microsite, $date)
    {

        $data   = array();
        $blocks = Block::where("ms_microsite_id", "=", $microsite)->where("start_date", "=", $date)->get();
        $i      = 0;
        foreach ($blocks as $block) {
            $blockTables = BlockTable::where("res_block_id", "=", $block->id)->get();
            foreach ($blockTables as $item) {
                $data[$i]["res_table_id"]              = $item->res_table_id;
                $data[$i]["res_block_id"]              = $block->id;
                $data[$i]["res_server_id"]             = null;
                $data[$i]["res_reservation_id"]        = null;
                $data[$i]["res_reservation_status_id"] = null;
                $data[$i]["num_people"]                = 0;
                $data[$i]["start_date"]                = $block->start_date;
                $data[$i]["start_time"]                = $block->start_time;
                $data[$i]["end_time"]                  = $block->end_time;
                $i++;
            }
        }

        $dataReservation = $this->getTablesReservation($microsite, $date);
        $response        = array_merge($data, $dataReservation);
        return $response;
    }

    public function getTablesReservation($microsite, $date)
    {

        $data = array();

        $reservations = res_reservation::with("server")->where("ms_microsite_id", "=", $microsite)->where("date_reservation", "=", $date)
            ->where('wait_list', '<>', 1)->get();

        $i = 0;
        foreach ($reservations as $reservation) {

            $tableReservations = TableReservation::where("res_reservation_id", "=", $reservation->id)->get();
            foreach ($tableReservations as $tableReservation) {
                $data[$i]["res_table_id"]              = $tableReservation->res_table_id;
                $data[$i]["res_block_id"]              = null;
                $data[$i]["res_server_id"]             = $reservation->res_server_id;
                $data[$i]["res_server"]                = $reservation->server;
                $data[$i]["res_reservation_id"]        = $tableReservation->res_reservation_id;
                $data[$i]["res_reservation_status_id"] = $reservation->res_reservation_status_id;
                $data[$i]["num_people"]                = $tableReservation->num_people;
                $data[$i]["start_date"]                = $reservation->date_reservation;
                $data[$i]["start_time"]                = $reservation->hours_reservation;
                $data[$i]["end_time"]                  = Utilitarios::sumarTiempos($reservation->hours_duration, $reservation->hours_reservation);
                $i++;
            }

        }
        return $data;
    }

    public function insert($microsite, $data)
    {

        DB::beginTransaction();
        try {

            $model                  = new Block();
            $model->start_date      = isset($data["start_date"]) ? $data["start_date"] : "";
            $model->start_time      = isset($data["start_time"]) ? $data["start_time"] : "";
            $model->end_time        = isset($data["end_time"]) ? $data["end_time"] : "";
            $model->ms_microsite_id = $microsite;

            $model->user_add = 1;
            $model->date_add = date("Y-m-d H:i:s");

            if (!$model->save()) {
                throw new Exception('messages.block_error_save_turn');
            }

            foreach ($data["tables"] as $table) {
                $blockTable               = new BlockTable();
                $blockTable->res_block_id = $model->id;
                $blockTable->res_table_id = $table["id"];
                if (!$blockTable->save()) {
                    throw new Exception('messages.block_error_save_turn');
                }
            }
            DB::commit();

            $response["mensaje"] = "messages.block_create_success";
            $response["estado"]  = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();
        }
        return (object) $response;

    }

    public function update($microsite, $block_id, $data)
    {

        DB::beginTransaction();
        try {

            $model = Block::find($block_id);

            if ($model === null) {
                throw new Exception('messages.block_not_exist');
            }

            $model->start_date = isset($data["start_date"]) ? $data["start_date"] : $model->start_date;
            $model->start_time = isset($data["start_time"]) ? $data["start_time"] : $model->start_time;
            $model->end_time   = isset($data["end_time"]) ? $data["end_time"] : $model->end_time;
            $model->user_upd   = 1;
            $model->date_upd   = date("Y-m-d H:i:s");

            if (!$model->update()) {
                throw new Exception('messages.block_error_update');
            }

            $blockTable = BlockTable::where("res_block_id", "=", $block_id);
            if (count($blockTable->get()) > 0) {
                if (!$blockTable->delete()) {
                    throw new Exception('messages.block_table_error_delete_turn');
                }
            }

            foreach ($data["tables"] as $table) {

                $blockTable               = new BlockTable();
                $blockTable->res_block_id = $model->id;
                $blockTable->res_table_id = $table["id"];
                if (!$blockTable->save()) {
                    throw new Exception('messages.block_error_update');
                }
            }
            DB::commit();

            $response["mensaje"] = "messages.block_update_success";
            $response["estado"]  = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();
        }
        return (object) $response;

    }

    public function delete($microsite, $block_id)
    {

        DB::beginTransaction();

        try {

            $block = Block::find($block_id);
            if ($block == null) {
                throw new Exception('messages.block_not_exist_turn');
            }
            if (!$block->delete()) {
                throw new Exception('messages.block_error_delete_tables');
            }

            $blockTable = BlockTable::where("res_block_id", "=", $block_id);
            if (!$blockTable->delete()) {
                throw new Exception('messages.block_table_error_delete_tables');
            }

            DB::commit();
            $response["mensaje"] = "messages.block_delete_success";
            $response["estado"]  = true;

        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"]  = false;
            DB::rollBack();

        }
        return (object) $response;
    }

}
