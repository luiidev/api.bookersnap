<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use DB;

class ev_event extends Model {

    protected $table = "ev_event";
    protected $hidden = ['ms_microsite_id', 'date_add', 'date_upd', 'date_del', 'user_add', 'user_upd', 'user_del'];

    public function type() {
        return $this->belongsTo('App\Entities\bs_type_event', 'bs_type_event_id');
    }

    public function turn() {
        return $this->belongsTo('App\res_turn', 'res_turn_id');
    }

    public function microsite() {
        return $this->belongsTo('App\Entities\ms_microsite');
    }

    public function turns() {
        return $this->hasMany('App\Entities\res_turn_promotion', 'ev_event_id');
    }

    /**
     * Promocion Grastuita activas en una fecha o rango de fecha
     * @param type $query
     * @param type $start_date
     * @param type $end_date
     * @return type
     */
    public function scopePromotionFreeActive($query, $start_date = null) {
        $datenow = \Carbon\Carbon::parse($start_date);
        return $query->where('ev_event.bs_type_event_id', 3)->where('ev_event.status', 1)
                        ->doesntHave('turns')
                        ->orWhereHas('turns', function($query) use ($datenow) {
                            return $query->whereHas('days', function($query) use ($datenow) {
                                        return $query->where('res_day_turn_promotion.day', $datenow->dayOfWeek);
                                    });
                        });
    }

    /**
     * Evento Grastuita activas en una fecha o rango de fecha
     * @param type $query
     * @param type $start_date
     * @param type $end_date
     * @return type
     */
    public function scopeEventFreeActive($query, $start_date = null, $end_date = null) {
        $end_date = is_null($end_date) ? $start_date : $end_date;
        return $query->where('ev_event.bs_type_event_id', 1)->where('ev_event.status', 1)
                        ->where(DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')"), ">=", $start_date)
                        ->where(DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')"),  "<=",$end_date);
//            ->whereRaw('res_turn_id in (select res_turn_id from res_turn where ms_microsite_id = ' . $microsite_id . ')')
    }

}
