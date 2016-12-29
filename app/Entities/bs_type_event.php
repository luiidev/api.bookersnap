<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

class bs_type_event extends Model
{
    const _ID_EVENT_FREE = 1;
    const _ID_EVENT_PAY = 2;
    const _ID_PROMOTION_FREE = 3;
    const _ID_PROMOTION_PAY = 4;
    
    const _BASEURL_IMG_EVENT = "http://bookersnap.com/archivo/eventos/800x800/";
    const _BASEURL_IMG_THUMB_EVENT = "http://bookersnap.com/archivo/eventos/160x160/";
    const _BASEURL_IMG_PROMOTION = "http://bookersnap.com/archivo/reservatiopromotion/800x800/";
    const _BASEURL_IMG_THUMB_PROMOTION = "http://bookersnap.com/archivo/reservatiopromotion/320x320/";

    protected $table = "bs_type_event";

    // public function events()
    // {
    //     return $this->hasMany('App\Entities\ev_event', 'bs_type_event_id');
    // }
}
