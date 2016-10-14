<?php

namespace App\Services;

use App\Entities\res_code;

class ConfigurationCodeService extends Service
{

    public function getCode()
    {
        return res_code::where('ms_microsite_id', (int) $this->microsite_id)->get();
    }

    public function createCode()
    {
        // return $this->properties;
        try {
            $code                  = new res_code();
            $code->code            = $this->req->code;
            $code->ms_microsite_id = $this->microsite_id;
            $code->save();
            return $code;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

    }

    public function updateCode()
    {
        $exists = res_code::where('ms_microsite_id', (int) $this->microsite_id)->where('code', $this->codes)->get();
        if ($exists != null) {
            res_code::where('ms_microsite_id', (int) $this->microsite_id)->where('code', $this->codes)->update(["code" => $this->req->code]);
            $codeUpdate = res_code::where('ms_microsite_id', (int) $this->microsite_id)->where('code', $this->req->code)->get();
            return $codeUpdate;
        } else {
            abort(404, "No existe el código");
        }
    }
    public function deleteCode()
    {
        $code = res_code::where('ms_microsite_id', (int) $this->microsite_id)->where('code', $this->codes)->delete();
        if ($code == true) {
            return true;
        } else {
            abort(404, "No existe código");
        }
    }

}
