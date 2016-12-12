<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Requests\GuestRequest;
use App\Services\GuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class GuestController extends Controller
{

    protected $_GuestService;

    public function __construct(GuestService $GuestService)
    {
        $this->_GuestService = $GuestService;
    }

    public function index(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $params       = $request->input();
        return $this->TryCatch(function () use ($microsite_id, $params) {
            $data = $this->_GuestService->getList($microsite_id, $params);
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function show(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $guest_id     = $request->route('guest_id');
        return $this->TryCatch(function () use ($microsite_id, $guest_id) {
            $result = $this->_GuestService->get($microsite_id, $guest_id);
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function create(GuestRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        return $this->TryCatch(function () use ($request, $microsite_id) {
            $result = $this->_GuestService->create($request->all(), $microsite_id);
            return $this->CreateResponse(true, 201, "", $result);
        });
    }

    public function update(GuestRequest $request)
    {
        $microsite_id = $request->route('microsite_id');
        $guest_id     = $request->route('guest_id');
        return $this->TryCatch(function () use ($request, $microsite_id, $guest_id) {
            $result = $this->_GuestService->update($request->all(), $guest_id);
            return response()->json($result);
        });
    }

    public function reservation(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $guest_id     = $request->route('guest_id');
        $params       = $request->input();
        return $this->TryCatch(function () use ($microsite_id, $guest_id, $params) {
            $result = $this->_GuestService->reservation($microsite_id, $guest_id, $params);
            return response()->json($result);
        });
    }

    //Controladores de TAG CUSTOM GUEST

    /**
     * Listas todas las Etiqueta Personalizadas
     * @param  Request $request [description]
     * @return array    Lista de Tag Personalizados de Guest
     */
    public function listGuestTag(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        return $this->TryCatch(function () use ($microsite_id) {
            $result = $this->_GuestService->getListTagCustom($microsite_id);
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

    /**
     * Crear Etiquetas Personalizadas
     * @param  Request $request nombre del tag a crear
     * @return Objeto   Detalle de Etiqueda Personalizada (id,name,status)
     */
    public function createGuestTag(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $params       = $request->input();
        return $this->TryCatch(function () use ($params, $microsite_id) {
            $result = $this->_GuestService->createTagCustom($params, $microsite_id);
            $this->_notification($microsite_id);
            return $this->CreateResponse(true, 200, "", $result);
        });
    }

    /**
     * Eliminar Etiqueta Personalizada
     * @param  string $language     Lenguaje de la ruta
     * @param  int $guest_tag_id Id de la lista a eliminar
     * @return Boolean               Devuelve true si se elimino correctamente caso contrario false
     */
    public function deleteGuestTag(Request $request)
    {
        $microsite_id = $request->route('microsite_id');
        $guest_tag_id = $request->route('guest_tag_id');
        return $this->TryCatch(function () use ($guest_tag_id, $microsite_id) {
            $result = $this->_GuestService->deleteTagCustom($guest_tag_id, $microsite_id);
            $this->_notification($microsite_id);
            return $this->CreateResponse(true, 200, "Se elimino tag seleccionado", $result);
        });
    }

    private function _notification(Int $microsite_id)
    {
        event(new EmitNotification("b-mesas-config-update",
            array(
                'microsite_id' => $microsite_id,
                'user_msg'     => 'Hay una actualización en la configuración (Tags).',
            )
        ));
    }
}
