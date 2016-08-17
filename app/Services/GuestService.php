<?php

namespace App\Services;

use App\res_guest;
use Illuminate\Support\Facades\DB;
use App\Services\GuestEmailService;

class GuestService {

    protected $_GuestEmailService;
    protected $_GuestPhoneService;

    public function __construct(GuestEmailService $GuestEmailService, GuestPhoneService $GuestPhoneService) {
        $this->_GuestEmailService = $GuestEmailService;
        $this->_GuestPhoneService = $GuestPhoneService;
        $this->_userId = 1;
    }

    public function getList(int $microsite_id, array $params) {

        $rows = res_guest::where('ms_microsite_id', $microsite_id)->with('emails')->with('phones');

        $name = !isset($params['name']) ? '' : $params['name'];
        $page_size = (!empty($params['page_size']) && $params['page_size'] <= 100) ? $params['page_size'] : 30;

        $rows = $rows->where('first_name', 'LIKE', '%' . $name . '%')->paginate($page_size);

        return $rows;
    }

    public function get(int $microsite_id, int $id) {
        try {
            $rows = res_guest::where('id', $id)->where('ms_microsite_id', $microsite_id)->with('emails')->with('phones')->first();

            if ($rows == null) {
                abort(500, "Ocurrio un error");
            }

            return $rows->toArray();
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function create(array $data, int $microsite_id) {

        try {
            $guest = new res_guest();
            $guest->first_name = $data['first_name'];
            $guest->last_name = empty($data['last_name']) ? null : $data['last_name'];
            $guest->birthdate = empty($data['birthdate']) ? null : $data['birthdate'];
            $guest->gender = empty($data['gender']) ? null : $data['gender'];
            $guest->ms_microsite_id = $microsite_id;
            $guest->user_add = $this->_userId;
            $guest->date_add = \Carbon\Carbon::now();

            DB::BeginTransaction();
            $guest->save();
            if (is_array($data['emails'])) {
                foreach ($data['emails'] as $value) {
                    $value["res_guest_id"] = $guest->id;
                    $this->_GuestEmailService->create($guest, $value);
                }
            }
            if (is_array($data['phones'])) {
                foreach ($data['phones'] as $value) {
                    $value["res_guest_id"] = $guest->id;
                    $this->_GuestPhoneService->create($guest, $value);
                }
            }
            DB::Commit();
            return $this->get($microsite_id, $guest->id);
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
    }

    public function update(array $data, int $id) {
        $response = false;
        try {
            $now = \Carbon\Carbon::now();
            $guest = res_guest::where('id', $id)->first();
            $guest->first_name = $data['first_name'];
            $guest->last_name = empty($data['last_name']) ? $guest->last_name : $data['last_name'];
            $guest->birthdate = empty($data['birthdate']) ? $guest->birthdate : $data['birthdate'];
            $guest->gender = empty($data['gender']) ? $guest->gender : $data['gender'];
            $guest->user_upd = $this->_userId;
            $guest->date_upd = $now;
            
            DB::BeginTransaction();

            $guest->save();

            if (is_array($data['emails'])) {
                foreach ($data['emails'] as $value) {
                    $this->_GuestEmailService->save($guest, $value);
                }
            }
            
            if (is_array($data['phones'])) {
                foreach ($data['phones'] as $value) {
                    $this->_GuestPhoneService->save($guest, $value);
                }
            }
            DB::Commit();
            $response = true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

        return $response;
    }

}
