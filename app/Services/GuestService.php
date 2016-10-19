<?php

namespace App\Services;

use App\res_guest;
use App\res_guest_tag;
use App\res_guest_tag_custom;
use App\res_reservation;
use App\Services\GuestEmailService;
use App\Services\GuestPhoneService;
use Illuminate\Support\Facades\DB;

class GuestService
{

    protected $_GuestEmailService;
    protected $_GuestPhoneService;

    public function __construct(GuestEmailService $GuestEmailService, GuestPhoneService $GuestPhoneService)
    {
        $this->_GuestEmailService = $GuestEmailService;
        $this->_GuestPhoneService = $GuestPhoneService;
        $this->_userId            = 1;
    }

    public function getList(int $microsite_id, array $params)
    {

        $rows = res_guest::where('ms_microsite_id', $microsite_id)->with('emails')->with('phones')->with('tags');

        $name      = !isset($params['name']) ? '' : $params['name'];
        $page_size = (!empty($params['page_size']) && $params['page_size'] <= 100) ? $params['page_size'] : 30;

        $rows = $rows->where('first_name', 'LIKE', '%' . $name . '%')->paginate($page_size);

        return $rows;
    }

    public function get(int $microsite_id, int $id)
    {
        try {
            $rows = res_guest::where('id', $id)->where('ms_microsite_id', $microsite_id)
                ->with('emails')
                ->with('phones')
                ->with('tags')
                ->with('customsTags')->first();
            return $rows;
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function create(array $data, int $microsite_id)
    {
        try {
            $guest                  = new res_guest();
            $guest->first_name      = $data['first_name'];
            $guest->last_name       = empty($data['last_name']) ? null : $data['last_name'];
            $guest->birthdate       = empty($data['birthdate']) ? null : $data['birthdate'];
            $guest->gender          = empty($data['gender']) ? null : $data['gender'];
            $guest->ms_microsite_id = $microsite_id;
            $guest->user_add        = $this->_userId;
            $guest->date_add        = \Carbon\Carbon::now();

            DB::BeginTransaction();
            $guest->save();
            is_array($data['emails']) ? $this->_GuestEmailService->saveAll($data['emails'], $guest->id) : false;
            is_array($data['phones']) ? $this->_GuestPhoneService->saveAll($data['phones'], $guest->id) : false;
            $this->asociateTags($data['tags'], $guest->id);
            DB::Commit();

            return $this->get($microsite_id, $guest->id);
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, "Ocurrio un error interno");
        }
    }

    public function update(array $data, int $id)
    {
        try {
            $guest             = res_guest::where('id', $id)->first();
            $guest->first_name = $data['first_name'];
            $guest->last_name  = empty($data['last_name']) ? $guest->last_name : $data['last_name'];
            $guest->birthdate  = empty($data['birthdate']) ? $guest->birthdate : $data['birthdate'];
            $guest->gender     = empty($data['gender']) ? $guest->gender : $data['gender'];
            $guest->user_upd   = $this->_userId;
            $guest->date_upd   = \Carbon\Carbon::now();

            DB::BeginTransaction();
            $guest->save();
            is_array($data['emails']) ? $this->_GuestEmailService->saveAll($data['emails'], $guest->id) : false;
            is_array($data['phones']) ? $this->_GuestPhoneService->saveAll($data['phones'], $guest->id) : false;
            $this->asociateTags($data['tags'], $guest->id);
            $this->asociateCustomTags($data['custom_tags'], $guest->id);
            DB::Commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
        return false;
    }

    //****************************************************************************************************************************************************
    //SERVICIO DE TAGS DE GUEST
    //****************************************************************************************************************************************************

    public function asociateTags(array $data, int $guest_id)
    {
        if (is_array($data)) {
            DB::table('res_guest_has_res_guest_tag')->where('res_guest_id', $guest_id)->delete();
            foreach ($data as $value) {
                $tag = res_guest_tag::where('id', $value["id"])->first();
                if ($tag == null) {
                    abort(500, "Ocurrio un error");
                }
                DB::table('res_guest_has_res_guest_tag')->insert([
                    'res_guest_id'     => $guest_id,
                    'res_guest_tag_id' => $tag->id,
                ]);
            }
        }
    }
    public function asociateCustomTags(array $data, int $guest_id)
    {
        if (is_array($data)) {
            DB::table('res_guest_tag_g')->where('res_guest_id', $guest_id)->delete();
            foreach ($data as $value) {
                $tag = res_guest_tag_custom::where('id', $value["id"])->first();
                if ($tag == null) {
                    abort(500, "Ocurrio un error");
                }
                DB::table('res_guest_tag_g')->insert([
                    'res_guest_id' => $guest_id,
                    'res_tag_g_id' => $tag->id,
                ]);
            }
        }
    }

    public function reservation(int $microsite_id, int $guest_id, array $params)
    {
        $page_size = (!empty($params['page_size']) && $params['page_size'] <= 100) ? $params['page_size'] : 30;
        $rows      = res_reservation::where('res_guest_id', $guest_id);
        $rows      = (!empty($params['start_date'])) ? $rows->where('date_reservation', '>=', $params['start_date']) : $rows;
        $rows      = (!empty($params['end_date'])) ? $rows->where('date_reservation', '<=', $params['end_date']) : $rows;
        $rows      = $rows->with('status')->with('tables')->paginate($page_size);
        return $rows;
    }

    //****************************************************************************************************************************************************
    //SERVICIO DE CUSTOM TAGS DE GUEST
    //****************************************************************************************************************************************************
    /**
     * Lista todos los tag custom guest
     * @param  int    $microsite_id [description]
     * @return array               Lista de todos los tags custom guest
     */
    public function getListTagCustom(int $microsite_id)
    {
        $list = res_guest_tag_custom::where('ms_microsite_id', $microsite_id)->get();
        return $list;
    }

    /**
     * Crea un nuevo tag guest custom
     * @param  array  $params       [description]
     * @param  int    $microsite_id [description]
     * @return object               Se devuelve el objeto creado
     */
    public function createTagCustom(array $params, int $microsite_id)
    {
        if (!empty($params['name'])) {
            $name      = ucfirst($params['name']);
            $tagCustom = res_guest_tag_custom::where('name', $name)->first();
            if ($tagCustom == null) {
                $tagCustom                  = new res_guest_tag_custom();
                $tagCustom->name            = $name;
                $tagCustom->status          = 1;
                $tagCustom->ms_microsite_id = $microsite_id;
                $tagCustom->save();
                return $tagCustom;
            }
        }
    }

    /**
     * Elimina Custom tag
     * @param  int    $guest_tag_id       [description]
     * @param  int    $microsite_id [description]
     * @return boolean               [description]
     */
    public function deleteTagCustom(int $guest_tag_id, int $microsite_id)
    {

        $tagCustom = res_guest_tag_custom::where('id', $guest_tag_id)
            ->where('ms_microsite_id', $microsite_id)
            ->first();
        if ($tagCustom != null) {
            $tagCustom->delete();
            return true;
        } else {
            return false;
        }
    }

}
