<?php

namespace App\Entities;

use App\res_reservation;
use Illuminate\Database\Eloquent\Model;

class bs_user extends Model
{
    protected $table   = "bs_user";
    protected $appends = ['urlphoto'];
    private $urlPath   = "/images/";
    protected $hidden = ["pivot"];

    public function logins()
    {
        return $this->hasMany('App\Entities\bs_userlogin', 'bs_user_id', 'id');
    }

    public function privileges()
    {
        return $this->belongsToMany('App\Entities\ms_microsite', 'res_privilege', 'bs_user_id', 'ms_microsite_id')->withPivot('date_add', 'user_add');
    }

    public function microsites()
    {
        return $this->hasMany('App\Entities\ms_microsite', 'bs_user_id', 'id');
    }

    public function socials()
    {
        //Relacion para acceder a las redes sociales de un usuario ('tabla a acceder','table intermedio','id intermedio','id de la tabla que se desea acceder','id foreingkey en la tabla intermedia')
        return $this->hasManythrough('App\Entities\bs_socialnetwork', 'App\Entities\bs_userlogin', 'bs_user_id', 'id', 'bs_socialnetwork_id');
    }

    public function getUrlphotoAttribute()
    {
        return $this->urlPath . $this->photo;
    }

    public function res_notifications()
    {
        return $this->belongsToMany(res_reservation::class, "res_notifications", "bs_user_id", "res_reservation_id");
    }

}
