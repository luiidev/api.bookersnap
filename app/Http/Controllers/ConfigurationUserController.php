<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfigurationUserRequest;
use App\Services\ConfigurationUserService;
use Illuminate\Http\Request;

class ConfigurationUserController extends Controller
{
    private $service;

    public function __construct(ConfigurationUserService $ConfigurationUserService)
    {
        $this->service = $ConfigurationUserService;
    }

    public function index(Request $request)
    {
        $microsite_id = $request->route("microsite_id");
        $users        = $this->service->getPrivilegeUsers($microsite_id);
        return $this->CreateJsonResponse(true, 200, "Lista de usuarios", $users);
    }

    public function store(ConfigurationUserRequest $request)
    {
        $microsite_id = $request->route("microsite_id");
        $user_id      = $request->user_id;
        $user_add     = $request->input("_bs_user_id");
        return $this->TryCatchDB(function () use ($microsite_id, $user_id, $user_add) {
            $response = $this->service->savePrivilegeUser($microsite_id, $user_id, $user_add);
            return $this->CreateJsonResponse(true, 200, "Se guardo privilegio", $response);
        });
    }

    public function destroy(Request $request)
    {
        $microsite_id = $request->route("microsite_id");
        $user_id      = $request->route("users");
        return $this->TryCatchDB(function () use ($microsite_id, $user_id) {
            $response = $this->service->deletePrivilegeUSer($microsite_id, $user_id);
            return $this->CreateJsonResponse(true, 200, "Se elimino privilegio", $response);
        });
    }

    public function getAllUser(Request $request)
    {
        $search = $request->search;
        return $this->TryCatchDB(function () use ($search) {
            $response = $this->service->getAllUser($search);
            return $this->CreateJsonResponse(true, 200, "", $response);
        });
    }

}
