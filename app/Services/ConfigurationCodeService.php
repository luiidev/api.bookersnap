<?php

namespace App\Services;

use App\Entities\res_code;

class ConfigurationCodeService
{
    private $lang;
    private $microsite_id;
    private $request;
    private $code_id;

    public function __construct($request)
    {
        $this->request                    = $request;
        $this->lang                       = $request->route("lang");
        $this->microsite_id               = $request->route("microsite_id");
        $this->code_id                    = $request->route('codes');
        $this->request["ms_microsite_id"] = $this->microsite_id;
    }

    public static function make($request)
    {
        return new static($request);
    }

    public function getCode()
    {
        return $codes = res_code::where('ms_microsite_id', $this->microsite_id)->get();
    }

    public function createCode()
    {
        try {
            $code                  = new res_code();
            $code->code            = $this->request["code"];
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
        $code->where('code', $this->code_id)->update($codeRequest);
        $code->update();

        $codeUpdate = res_code::find($this->code_id);

        return $codeUpdate;
    }
    public function deleteCode()
    {
        $code = res_code::find($this->code_id);
        $code->delete();
        return $code;
    }

}
