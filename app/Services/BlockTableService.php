<?php

namespace App\Services;

use App\Entities\BlockTable;

class BlockTableService
{
    public function deleteTable(int $tableId, int $blockId)
    {
        BlockTable::where(array("res_block_id" => $blockId, "res_table_id" => $tableId))->delete();
    }

    public function addTable(int $blockId, array $tables)
    {
        foreach ($tables as $key => $table) {
            $blockTable               = new BlockTable();
            $blockTable->res_table_id = $table;
            $blockTable->res_block_id = $blockId;
            $blockTable->save();
        }

    }

}
