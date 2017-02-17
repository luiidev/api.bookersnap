<?php

namespace App\Entities;

use App\Entities\res_form;
use App\Entities\res_form_configuration;
use Illuminate\Database\Eloquent\Model;

class ms_microsite extends Model
{
    const _BASEURL_IMG_LOGO = "http://bookersnap.com/archivo/img-logo/80x80";
    const _BASEURL_IMG_FAVICON = "http://bookersnap.com/archivo/img-favicon/36x36";

    protected $table = "ms_microsite";
    protected $hidden = ['pivot'];

    public function privileges()
    {
        return $this->belongsToMany('App\Entities\bs_user', 'res_privilege', 'ms_microsite_id', 'bs_user_id')->withPivot('date_add', 'user_add');
    }

    public function creator()
    {
        return $this->belongsTo('App\Entities\bs_user', 'bs_user_id', 'id');
    }

    public function country()
    {
    	return $this->belongsTo(bs_country::class, "bs_country_id");
    }

    public function configuration()
    {
        return $this->hasOne(res_configuration::class, "ms_microsite_id");
    }

    public function form()
    {
        return $this->belongsToMany(res_form::class, "res_form_configuration");
    }
    
}
