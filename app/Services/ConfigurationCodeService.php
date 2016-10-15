<?php

namespace App\Services;

use App\Entities\res_code;

class ConfigurationCodeService
{

    public function getCode(int $microsite_id)
    {
        return res_code::where('ms_microsite_id', $microsite_id)->get();
    }

    public function createCode(int $microsite_id, array $input)
    {

        try {
            $code                  = new res_code();
            $code->code            = $input["code"];
            $code->ms_microsite_id = $microsite_id;
            $code->save();
            return $code;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

    }

    public function updateCode(int $microsite_id, string $code, array $input)
    {
        $exists = res_code::where('ms_microsite_id', $microsite_id)->where('code', $code)->get();
        if ($exists != null) {
            res_code::where('ms_microsite_id', $microsite_id)->where('code', $code)->update(["code" => $input["code"]]);
            $codeUpdate = res_code::where('ms_microsite_id', $microsite_id)->where('code', $input["code"])->get();
            return $codeUpdate;
        } else {
            abort(404, "No existe el código");
        }
    }

    public function deleteCode(int $microsite_id, string $codes)
    {
        $code = res_code::where('ms_microsite_id', $microsite_id)->where('code', $codes)->delete();
        if ($code == true) {
            return true;
        } else {
            abort(404, "No existe código");
        }
    }

}
