<?php

namespace App\Services;

use App\res_guest_tag_category;
use Illuminate\Support\Facades\DB;

class GuestTagCategoryService {

    public function getList() {
        $rows = res_guest_tag_category::with('tags')->get();
        return $rows;
    }

    public function get(int $id) {
        try {
            $rows = res_guest_tag_category::where('id', $id)->with('tags')->first();
            return $rows;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

}
