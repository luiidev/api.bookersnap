<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ZoneTurnRequest;
use App\res_zone;
use App\res_table;
use App\res_day_turn_zone;
use App\res_type_turn_zone;
use App\res_turn_zone;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Services\ZoneTurnService;

class ZoneTurnController extends Controller {

    protected $_ZoneTurnService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(ZoneTurnService $ZoneTurnService) {
        $this->_ZoneTurnService = $ZoneTurnService;
    }

    /**
     * mostrar todos los turnos de una zona con sus dias.
     * @param string    $lang       Lenguaje
     * @param int       $zone_id    Identificador de la zona
     * @return json
     */
    public function index($lang, int $zone_id) {
        $result = $this->_ZoneTurnService->getList($zone_id);
        return response()->json($result);
    }

    /**
     * listar zona id y sus mesas.
     * @param micrositio_id
     * @return una zona y sus mesas
     */
    public function show($lang, int $zone_id, int $id) {
        $result = $this->_ZoneTurnService->get($zone_id, $id);
        return response()->json($result);
    }

    public function store(ZoneTurnRequest $request, $lang, int $zone_id) {

        $save = $this->_ZoneTurnService->create($zone_id, $request->all());
        $result = $this->_ZoneTurnService->getList($zone_id);
        return response()->json($result);
    }

    public function update(ZoneTurnRequest $request, $lang, int $zone_id, int $id) {

        $save = $this->_ZoneTurnService->update($id, $zone_id, $request->all());
        $result = $this->_ZoneTurnService->getList($zone_id);
        return response()->json($result);
    }

    public function delete($lang, int $zone_id, int $id) {

        $save = $this->_ZoneTurnService->delete($id, $zone_id);
        $result = $this->_ZoneTurnService->getList($zone_id);
        return response()->json($result);
    }

}
