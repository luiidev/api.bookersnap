<?php

namespace App\Services;

use App\Entities\bs_user;
use App\res_notification;
use App\res_reservation;

class NotificationService extends Service
{
    public function index()
    {
        $paginate = res_reservation::with("guest")->where("ms_microsite_id", $this->microsite_id)->where("res_source_type_id", 4)->simplePaginate(5);
        $views = res_notification::where("bs_user_id", $this->req->_bs_user_id)->get()->pluck("res_reservation_id");
        $count = res_reservation::where("ms_microsite_id", $this->microsite_id)->whereNotIn("id", $views)->count();

        return ["paginate" => $paginate, "notification_count" => $count];
    }

    public function update()
    {
        $ids = res_reservation::select("id")->where("ms_microsite_id", $this->microsite_id)->get()->pluck("id");

        $user = bs_user::find($this->req->_bs_user_id);
        $user->res_notifications()->sync($ids->toArray());
    }
}