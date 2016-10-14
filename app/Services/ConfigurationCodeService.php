<?php

namespace App\Services;

use App\Entities\res_code;

class ConfigurationCodeService extends Service
{

    public function getCode()
    {
        return res_code::where('ms_microsite_id', $this->microsite_id)->get();
    }

    public function createCode()
    {
        // return $this->properties;
        try {
            $code                  = new res_code();
            $code->code            = $this->codes;
            $code->ms_microsite_id = $this->microsite_id;
            $code->save();
            return $code;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }

    }

    public function updateCode()
    {
        $codeRequest = $this->request->all();
        unset($codeRequest["_bs_user_id"]);
        $code->where('code', $this->codes)->update($codeRequest);
        $code->update();

        $codeUpdate = res_code::find($this->codes);

        return $codeUpdate;
    }
    public function deleteCode()
    {
        $code = res_code::find($this->codes);
        $code->delete();
        return $code;
    }

}
