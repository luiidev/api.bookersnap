<?php

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;
use DB;
use Carbon\Carbon;

class ev_event extends Model {

    protected $table = "ev_event";
    protected $hidden = ['ms_microsite_id', 'date_add', 'date_upd', 'date_del', 'user_add', 'user_upd', 'user_del'];

    const _ID_EVENT_FREE = 1;
    const _ID_PROMOTION_FREE = 3;

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

    //HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHH          HHHHHH            HHHHH              HHHHH            HHHHH            HHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHH   HHHHHHHHHHHHH   HHHHHHHHHHHHHH   HHHHHHHH   HHHHH   HHHHHH   HHHHH   HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHH   HHHHHHHHHHHHH   HHHHHHHHHHHHHH   HHHHHHHH   HHHHH   HHHHHH   HHHHH   HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHH          HHHHHH   HHHHHHHHHHHHHH   HHHHHHHH   HHHHH   HHHHHH   HHHHH       HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHHHHHHHHH   HHHHHH   HHHHHHHHHHHHHH   HHHHHHHH   HHHHH           HHHHHH   HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHHHHHHHHH   HHHHHH   HHHHHHHHHHHHHH   HHHHHHHH   HHHHH   HHHHHHHHHHHHHH   HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHH          HHHHHH            HHHHH              HHHHH   HHHHHHHHHHHHHH            HHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH
    //HHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHHH

    /**
     * Eventos Gratuitos
     */
    public function scopeEventFree($query) {
        return $query->where('ev_event.bs_type_event_id', self::_ID_EVENT_FREE);
    }

    /**
     * Promociones Gratuitas
     */
    public function scopePromotionFree($query) {
        return $query->where('ev_event.bs_type_event_id', self::_ID_PROMOTION_FREE);
    }

    /**
     * Eventos activo en una fecha
     * @param type $query
     * @param type $date
     * @return type
     */
    public function scopeEnableInDate($query, $date = null) {

        $datenow = !is_null($date) ? Carbon::parse($date) : Carbon::now();

        $query = $query->where(function($query) use ($datenow) {
            $query = $query->promotionFree();
            $query = $query->doesntHave('turns');   // para promciones que se aplican todos los dias de la semana.         
            $query = $query->orWhereHas('turns', function($query) use ($datenow) {
                $query = $query->whereHas('days', function($query) use ($datenow) {
                    $query = $query->where('res_day_turn_promotion.day', $datenow->dayOfWeek);
                    return $query;
                });
                return $query;
            });
            return $query;
        });

        $query = $query->orWhere(function($query) use ($datenow) {
            $query = $query->eventFree();
            $query = $query->whereHas('turn', function($query) use ($datenow) {
                $query = $query->where(DB::raw("DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')"), $datenow->toDateString());
                return $query;
            });
        });
        return $query;
    }

    /**
     * Eventos Grastuitos activos en una fecha o rango de fecha
     * @param type $query
     * @param type $start_date
     * @param type $end_date
     * @return type
     */
    public function scopeEventFreeActive($query, $start_date = null, $end_date = null) {

        $end_date = is_null($end_date) ? $start_date : $end_date;

        $now = Carbon::now();
        $yesterday = $now->copy()->yesterday();

        $query = $query->where('ev_event.bs_type_event_id', 1)->where('ev_event.status', 1)->whereHas('turn', function($query) use ($now, $yesterday) {
            $dateformat = "DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')";
            $datetimeformat = "CONCAT('" . $now->toDateString() . " ', res_turn.hours_end)";
            return $query->where(DB::raw($dateformat), $yesterday->toDateString())->whereRaw("res_turn.hours_ini > res_turn.hours_end")->where(DB::raw($datetimeformat), ">=", $now->toDateTimeString())
                            ->orWhere(DB::raw($dateformat), ">=", $now->toDateString());
        });

        if (!is_null($start_date)) {
            $dateformat = "DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')";
            $query = $query->where(DB::raw($dateformat), ">=", $start_date);
        }
        if (!is_null($end_date)) {
            $dateformat = "DATE_FORMAT(ev_event.datetime_event, '%Y-%m-%d')";
            $query = $query->where(DB::raw($dateformat), "<=", $end_date);
        }
        return $query;
    }

}
