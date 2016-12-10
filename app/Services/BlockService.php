<?php

namespace App\Services;

use App\Entities\Block;
use App\Entities\BlockTable;
use App\Entities\TableReservation;
use App\Helpers\Utilitarios;
use App\res_reservation;
use DB;
use Exception;
use App\Services\Helpers\CalendarHelper;

class BlockService {

    public function getBlock($microsite, $id_block) {
        $block = Block::with('tables')->find($id_block);

        return $block;
    }

    public function listado(int $microsite_id, string $date = null) {
        
        if (is_null($date)) {
            $date = CalendarHelper::realDate($microsite_id);
        }
        $realDateTimeOpen = CalendarHelper::realDateTimeOpen($microsite_id, $date);
        $realDateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $date);

        $data = array();
        $blocks = Block::with('tables')->where("ms_microsite_id", "=", $microsite_id)
                        ->whereRaw("CONCAT(res_block.start_date, ' ', res_block.start_time) BETWEEN ? AND ?", array($realDateTimeOpen, $realDateTimeClose))->get();

        return $blocks;
    }

    public function getTables(int $microsite_id, string $date = null) {
        
        list($datetimeOpen, $datetimeClose) = CalendarHelper::realDateTimeOpenAndClose($microsite_id, $date);

        $data = array();
        $blocks = Block::where("ms_microsite_id", "=", $microsite_id)
                        ->whereRaw("CONCAT(res_block.start_date, ' ', res_block.start_time) BETWEEN ? AND ?", array($datetimeOpen, $datetimeClose))->get();
        $i = 0;
        foreach ($blocks as $block) {
            $blockTables = BlockTable::where("res_block_id", "=", $block->id)->get();
            foreach ($blockTables as $item) {
                $data[$i]["res_table_id"] = $item->res_table_id;
                $data[$i]["res_block_id"] = $block->id;
                $data[$i]["res_server_id"] = null;
                $data[$i]["res_reservation_id"] = null;
                $data[$i]["res_reservation_status_id"] = null;
                $data[$i]["num_people"] = 0;
                $data[$i]["start_date"] = $block->start_date;
                $data[$i]["start_time"] = $block->start_time;
                $data[$i]["end_time"] = $block->end_time;
                $i++;
            }
        }

        $dataReservation = $this->getTablesReservation($microsite_id, $date);
        $response = array_merge($data, $dataReservation);
        return $response;
    }

    public function getTablesReservation(int $microsite_id, string $date) {
        
        list($datetimeOpen, $datetimeClose) = CalendarHelper::realDateTimeOpenAndClose($microsite_id, $date);
        $data = array();

        $reservations = res_reservation::with("server")->where("ms_microsite_id", "=", $microsite_id)
                ->whereRaw("CONCAT(date_reservation, ' ', hours_reservation) BETWEEN ? AND ?", array($datetimeOpen, $datetimeClose))->get();

        $i = 0;
        foreach ($reservations as $reservation) {

            $tableReservations = TableReservation::where("res_reservation_id", "=", $reservation->id)->get();
            foreach ($tableReservations as $tableReservation) {
                $data[$i]["res_table_id"] = $tableReservation->res_table_id;
                $data[$i]["res_block_id"] = null;
                $data[$i]["res_server_id"] = $reservation->res_server_id;
                $data[$i]["res_server"] = $reservation->server;
                $data[$i]["res_reservation_id"] = $tableReservation->res_reservation_id;
                $data[$i]["res_reservation_status_id"] = $reservation->res_reservation_status_id;
                $data[$i]["num_people"] = $tableReservation->num_people;
                $data[$i]["start_date"] = $reservation->date_reservation;
                $data[$i]["start_time"] = $reservation->hours_reservation;
                $data[$i]["end_time"] = Utilitarios::sumarTiempos($reservation->hours_duration, $reservation->hours_reservation);
                $i++;
            }
        }
        return $data;
    }

    public function insert($microsite, $data) {

        DB::beginTransaction();
        try {

            $model = new Block();
            $model->start_date = isset($data["start_date"]) ? $data["start_date"] : "";
            $model->end_date = $model->start_date;
            $model->start_time = isset($data["start_time"]) ? $data["start_time"] : "";
            $model->end_time = isset($data["end_time"]) ? $data["end_time"] : "";
            $model->ms_microsite_id = $microsite;

            $model->user_add = 1;

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
            $response["block_id"] = $model->id;
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

            if ($model === null) {
                throw new Exception('messages.block_not_exist');
            }

            $model->start_date = isset($data["start_date"]) ? $data["start_date"] : $model->start_date;
            $model->end_date = $model->start_date;
            $model->start_time = isset($data["start_time"]) ? $data["start_time"] : $model->start_time;
            $model->end_time = isset($data["end_time"]) ? $data["end_time"] : $model->end_time;
            $model->user_upd = 1;

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
            $data = Block::with("tables")->find($block_id);
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
            $response["estado"] = true;
            $response["block"] = $data;
        } catch (\Exception $e) {

            $response["mensaje"] = $e->getMessage();
            $response["estado"] = false;
            DB::rollBack();
        }
        return (object) $response;
    }

}
