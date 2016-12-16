<?php

namespace App\Http\Controllers;

use App\res_turn;
use App\Services\BlockService;
use App\Services\CalendarService;
use App\Services\Helpers\CalendarHelper;
use App\Services\TableService;
use App\Services\TurnService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class WebAppController extends Controller
{

    protected $_TurnService;
    protected $_BlockService;
    protected $_CalendarService;
    protected $_TableService;

    public function __construct(TurnService $TurnService, BlockService $BlockService, CalendarService $CalendarService, TableService $TableService)
    {
        $this->_TurnService     = $TurnService;
        $this->_BlockService    = $BlockService;
        $this->_CalendarService = $CalendarService;
        $this->_TableService    = $TableService;
    }

    public function floor(Request $request)
    {
        return $this->TryCatch(function () use ($request) {

            $microsite_id  = $request->route('microsite_id');
            $date          = CalendarHelper::realDate($microsite_id);
            $dateTimeOpen  = CalendarHelper::realDateTimeOpen($microsite_id, $date);
            $dateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $date);

            $turnsIds      = $this->turnsIdsByDate($microsite_id, $date);
            $turns         = $this->turnsByIds($turnsIds);
            $zones         = $this->zonesIdsByTurnsIds($turnsIds);
            $blocks        = $this->blocksByRangeDate($microsite_id, $dateTimeOpen, $dateTimeClose);
            $reservations  = $this->reservationsByDate($microsite_id, $date);
            $configuration = $this->configuration($microsite_id);
            $servers       = $this->servers($microsite_id);
            $sourceTypes   = $this->souceTypes();
            $notes         = $this->notes($microsite_id, $date);
            $shifts        = $this->shifts($turnsIds, $notes);
            $tags          = $this->tagsReservations($microsite_id);
            $status        = $this->status();

            $data = [
                "schedule"     => [
                    "dateOpen"      => $date,
                    "datetimeOpen"  => $dateTimeOpen,
                    "datetimeClose" => $dateTimeClose,
                ],
                "config"       => $configuration,
                "status"       => $status,
                "tags"         => $tags,
                "turns"        => $turns,
                "shifts"       => $shifts,
                "zones"        => $zones,
                "blocks"       => $blocks,
                "reservations" => $reservations,
                "servers"      => $servers,
                "sourceTypes"  => $sourceTypes,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function book(Request $request)
    {
        return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = CalendarHelper::realDate($microsite_id);
            }
            $end_date = $date;

            $dateTimeOpen  = CalendarHelper::realDateTimeOpen($microsite_id, $date);
            $dateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $date);
            
            $turnsIds = $this->turnsIdsByDate($microsite_id, $date);
            $turns    = $this->turnsByIds($turnsIds);
            $zones    = $this->zonesIdsByTurnsIds($turnsIds);

            $reservations = $this->bookReservations($microsite_id, $date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $configuration = $this->configuration($microsite_id);
            $servers       = $this->servers($microsite_id);
            $sourceTypes   = $this->souceTypes();
            $notes         = $this->notes($microsite_id, $date);
            $shifts        = $this->shifts($turnsIds, $notes);
            $tags          = $this->tagsReservations($microsite_id);
            $status        = $this->status();
            $blocks        = $this->blocksByRangeDate($microsite_id, $dateTimeOpen, $dateTimeClose);

            $sentados        = $this->numSentadosReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $pax             = $this->paxReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasOcupadas   = $this->mesasOcupadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasReservadas = $this->mesasReservadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            
            $availebility = $this->_TableService->tablesAvailability($microsite_id, $date);

            $reservaSentadas = $this->reservacionesSentadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $TOTAL       = $reservations->count();
            $PAX         = $pax->PAX;
            $PAX_INGRESO = ($pax->PAX01 + $pax->PAX02 + $pax->PAX03);
            $CONVERSION  = ($TOTAL > 0) ? number_format(($reservaSentadas / $TOTAL) * 100, 2) : 0;
            $schedule    = [
                "dateOpen"      => $date,
                "datetimeOpen"  => $dateTimeOpen,
                "datetimeClose" => $dateTimeClose,
            ];
            $stadistics = [
                "TOTAL"            => $TOTAL,
                "PAX"              => $PAX,
                "PAX_INGRESO"      => $PAX_INGRESO,
                "CONVERSION"       => $CONVERSION,
                "MESAS_OCUPADAS"   => $mesasOcupadas,
                "MESAS_RESERVADAS" => $mesasReservadas,
            ];
            $data = [
                "schedule"           => $schedule,
                "stadistics"         => $stadistics,
                "reservations"       => $reservations,
                "availabilityTables" => $availebility,
                "config"             => $configuration,
                "status"             => $status,
                "tags"               => $tags,
                "turns"              => $turns,
                "shifts"             => $shifts,
                "zones"              => $zones,
                "servers"            => $servers,
                "sourceTypes"        => $sourceTypes,
                "blocks"             => $blocks,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function bookHistory(Request $request)
    {
        return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = CalendarHelper::realDate($microsite_id);
            }
            if ($request->has("date_end")) {
                $end_date = (strcmp($request->input('date_end'), $date) > 0) ? $request->input('date_end') : $date;
            } else {
                $end_date = $date;
            }

            $dateTimeOpen  = CalendarHelper::realDateTimeOpen($microsite_id, $date);
            $dateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $end_date);

            $turnsIds = $this->turnsIdsByRangeDate($microsite_id, $date, $end_date);
            $turns    = $this->turnsByIds($turnsIds);
            $zones    = $this->zonesIdsByTurnsIds($turnsIds);
            $blocks   = $this->blocksByRangeDate($microsite_id, $dateTimeOpen, $dateTimeClose);

            $reservations = $this->searchReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $configuration = $this->configuration($microsite_id);
            $servers       = $this->servers($microsite_id);
            $sourceTypes   = $this->souceTypes();
            $notes         = $this->notes($microsite_id, $date);
            $shifts        = $this->shifts($turnsIds, $notes);
            $tags          = $this->tagsReservations($microsite_id);
            $status        = $this->status();

            $sentados        = $this->numSentadosReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $pax             = $this->paxReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasOcupadas   = $this->mesasOcupadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasReservadas = $this->mesasReservadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $reservaSentadas = $this->reservacionesSentadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $TOTAL       = $reservations->total();
            $PAX         = $pax->PAX;
            $PAX_INGRESO = ($pax->PAX01 + $pax->PAX02 + $pax->PAX03);
            $CONVERSION  = ($TOTAL > 0) ? number_format(($reservaSentadas / $TOTAL) * 100, 2) : 0;

            $schedule = [
                "dateOpen"      => $date,
                "datetimeOpen"  => $dateTimeOpen,
                "datetimeClose" => $dateTimeClose,
            ];
            $stadistics = [
                "TOTAL"            => $TOTAL,
                "PAX"              => $PAX,
                "PAX_INGRESO"      => $PAX_INGRESO,
                "CONVERSION"       => $CONVERSION,
                "MESAS_OCUPADAS"   => $mesasOcupadas,
                "MESAS_RESERVADAS" => $mesasReservadas,
            ];
            $data = [
                "schedule"     => $schedule,
                "stadistics"   => $stadistics,
                "reservations" => $reservations,
                "config"       => $configuration,
                "status"       => $status,
                "tags"         => $tags,
                "turns"        => $turns,
                "shifts"       => $shifts,
                "zones"        => $zones,
                "servers"      => $servers,
                "sourceTypes"  => $sourceTypes,
                "blocks"       => $blocks,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function bookHistoryReservations(Request $request)
    {

        return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = CalendarHelper::realDate($microsite_id);
            }
            if ($request->has("date_end")) {
                $end_date = (strcmp($request->input('date_end'), $date) > 0) ? $request->input('date_end') : $date;
            } else {
                $end_date = $date;
            }

            $dateTimeOpen  = CalendarHelper::realDateTimeOpen($microsite_id, $date);
            $dateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $end_date);

            $turnsIds = $this->turnsIdsByRangeDate($microsite_id, $date, $end_date);
            $turns    = $this->turnsByIds($turnsIds);
            $zones    = $this->zonesIdsByTurnsIds($turnsIds);

            $reservations = $this->searchReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $sentados        = $this->numSentadosReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $pax             = $this->paxReservations($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasOcupadas   = $this->mesasOcupadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $mesasReservadas = $this->mesasReservadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));
            $reservaSentadas = $this->reservacionesSentadas($microsite_id, $date, $end_date, $request->input('search_text'), $request->input('sort'), $request->input('turns'), $request->input('sources'), $request->input('zones'), $request->input('page_size'));

            $TOTAL       = $reservations->total();
            $PAX         = $pax->PAX;
            $PAX_INGRESO = ($pax->PAX01 + $pax->PAX02 + $pax->PAX03);
            $CONVERSION  = ($TOTAL > 0) ? number_format(($reservaSentadas / $TOTAL) * 100, 2) : 0;

            $schedule = [
                "dateOpen"      => $date,
                "datetimeOpen"  => $dateTimeOpen,
                "datetimeClose" => $dateTimeClose,
            ];
            $stadistics = [
                "TOTAL"            => $TOTAL,
                "PAX"              => $PAX,
                "PAX_INGRESO"      => $PAX_INGRESO,
                "CONVERSION"       => $CONVERSION,
                "MESAS_OCUPADAS"   => $mesasOcupadas,
                "MESAS_RESERVADAS" => $mesasReservadas,
            ];
            $data = [
                "schedule"     => $schedule,
                "stadistics"   => $stadistics,
                "reservations" => $reservations,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function editReservation(Request $request)
    {

        return $this->TryCatch(function () use ($request) {

            $microsite_id   = $request->route('microsite_id');
            $reservation_id = $request->route('reservation_id');

            $reservation = $this->reservation($microsite_id, $reservation_id);
            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = ($reservation) ? $reservation->date_reservation : CalendarHelper::realDate($microsite_id);
            }

            $turnsIds      = $this->turnsIdsByDate($microsite_id, $date);
            $zones         = $this->zonesIdsByTurnsIds($turnsIds);
            $configuration = $this->configuration($microsite_id);
            $servers       = $this->servers($microsite_id);
            $tags          = $this->tagsReservations($microsite_id);
            $sourceTypes   = $this->souceTypes();
            $status        = $this->status();
            $notes         = $this->notes($microsite_id, $date);
            $shifts        = $this->shifts($turnsIds, $notes);
            $blockTables   = $this->_BlockService->getTables($microsite_id, $date);
            $reservations  = $this->reservationsByDate($microsite_id, $date);

            $data = [
                "reservation"  => $reservation,
                "zones"        => $zones,
                "config"       => $configuration,
                "status"       => $status,
                "servers"      => $servers,
                "tags"         => $tags,
                "shifts"       => $shifts,
                "sourceTypes"  => $sourceTypes,
                "blockTables"  => $blockTables,
                "reservations" => $reservations,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function editBlock(Request $request)
    {

        return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            $block_id     = $request->route('block_id');

            $block = $this->block($microsite_id, $block_id);

            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = ($block) ? $block->start_date : CalendarHelper::realDate($microsite_id);
            }

            $turnsIds    = $this->turnsIdsByDate($microsite_id, $date);
            $zones       = $this->zonesIdsByTurnsIds($turnsIds);
            $notes       = $this->notes($microsite_id, $date);
            $shifts      = $this->shifts($turnsIds, $notes);
            $blockTables = $this->_BlockService->getTables($microsite_id, $date);
            $data        = [
                "block"       => $block,
                "zones"       => $zones,
                "shifts"      => $shifts,
                "blockTables" => $blockTables,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }

    public function paxReservations($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select(DB::raw("SUM(res.num_guest) AS PAX, SUM(res.num_people_1) AS PAX01, SUM(res.num_people_2) AS PAX02, SUM(res.num_people_3) AS PAX03"))->from("res_reservation as res")->where("res.wait_list", 0);
        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
//        return $reservations->toSql();
        return $reservations->first();
    }

    public function numSentadosReservations($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select(DB::raw("COUNT(res.id) AS SENTADOS"))->from("res_reservation as res")->where("res.wait_list", 0);
        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
//        return $reservations->toSql();
        $reservations = $reservations->where('res.res_reservation_status_id', 4);
        return $reservations->first()->SENTADOS;
    }

    public function mesasOcupadas($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select(DB::raw("COUNT(res.id) AS SENTADOS"))->from("res_reservation as res")->where("res.wait_list", 0);
        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
//        return $reservations->toSql();
        $reservations = $reservations->where('res.res_reservation_status_id', 4);
        $reservations = $reservations->join("res_table_reservation as t_res", "t_res.res_reservation_id", "=", "res.id");
        return $reservations->first()->SENTADOS;
    }

    public function reservacionesSentadas($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select(DB::raw("COUNT(res.id) AS SENTADOS"))->from("res_reservation as res")->where("res.wait_list", 0);
        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
        $reservations = $reservations->where('res.res_reservation_status_id', 4);
        return $reservations->first()->SENTADOS;
    }

    public function mesasReservadas($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select(DB::raw("COUNT(res.id) AS SENTADOS"))->from("res_reservation as res")->where("res.wait_list", 0);
        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
//        return $reservations->toSql();
        $reservations = $reservations->join("res_table_reservation as t_res", "t_res.res_reservation_id", "=", "res.id");
        return $reservations->first()->SENTADOS;
    }

    public function bookReservations($microsite_id, $date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select("res.*")->with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList", "emails", "event"])->from("res_reservation as res")->where("res.wait_list", 0);

        $this->queryReservation($reservations, $microsite_id, $date, $date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
        //return $reservations->toSql();
        $sortBy       = $this->getNameSort($sortBy);
        $reservations = $reservations->orderBy($sortBy->value, $sortBy->type);

        return $reservations->get();
    }

    public function searchReservations($microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds, $page_size = 30)
    {
        $reservations = \App\res_reservation::select("res.*")->with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList", "emails", "event"])->from("res_reservation as res")->where("res.wait_list", 0);

        $this->queryReservation($reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds);
        //return $reservations->toSql();

        $sortBy = $this->getNameSort($sortBy);
        // dd($sortBy);
        $reservations = $reservations->orderBy($sortBy->value, $sortBy->type);

        return $reservations->paginate($page_size);
    }

    private function queryReservation(&$reservations, $microsite_id, $start_date, $end_date, $searchText, $sortBy, $turnIds, $sourceIds, $zoneIds)
    {

        $turnIds   = ($turnIds) ? explode(",", $turnIds) : [];
        $sourceIds = ($sourceIds) ? explode(",", $sourceIds) : [];
        $zoneIds   = ($zoneIds) ? explode(",", $zoneIds) : [];

        if (strlen(trim($searchText)) > 0 || $sortBy == "guest.asc" || $sortBy == "guest.desc" || $sortBy === "guest") {
            $reservations = $reservations->join("res_guest as guest", "guest.id", "=", "res.res_guest_id");
            if (strlen(trim($searchText)) > 0) {
                $reservations = $reservations->where("guest.first_name", "LIKE", "%" . $searchText . "%");
            }
        }
        if ($sortBy == "table.asc" || $sortBy == "table.desc") {
            $reservations = $reservations->join("res_table_reservation as table_res", "table_res.res_reservation_id", "=", "res.id");
            $reservations = $reservations->join("res_table as table", "table.id", "=", "table_res.res_table_id");
        }

        $reservations = $reservations->whereBetween("res.date_reservation", [$start_date, $end_date]);

        if (count($turnIds) > 0) {
            $typeTurns    = \App\res_turn::whereIn('id', $turnIds)->groupBy('res_type_turn_id')->pluck('res_type_turn_id')->toArray();
            $reservations = $reservations->join('res_turn', function ($join) {
                $join->on('res_turn.id', '=', 'res.res_turn_id');
            });
            if (count($typeTurns) > 0) {
                $reservations = $reservations->join('res_type_turn', function ($join) use ($typeTurns) {
                    $join->on('res_type_turn.id', '=', 'res_turn.res_type_turn_id')->whereIn('res_type_turn.id', $typeTurns);
                });
            }
        }

        if (count($sourceIds) > 0) {
            $reservations = $reservations->whereIn('res.res_source_type_id', $sourceIds);
        }

        if (count($zoneIds) > 0) {

            $reservations = $reservations->join('res_table_reservation', function ($join) {
                $join->on('res_table_reservation.res_reservation_id', '=', 'res.id');
            });

            $reservations = $reservations->join('res_table', function ($join) use ($zoneIds) {
                $join->on('res_table.id', '=', 'res_table_reservation.res_table_id')
                    ->whereIn('res_table.res_zone_id', $zoneIds);
            });
        }

        $reservations->where('res.ms_microsite_id', $microsite_id);
        return $reservations;
    }

    private function getNameSort(string $value = null)
    {
        if (is_null($value)) {
            return (object) ["value" => "datetime_input", "type" => "asc"];
        }
        $order        = explode(".", $value);
        $sort['type'] = (@$order[1] && $order[1] != "desc") ? "asc" : "desc";

        switch ($order[0]) {
            case 'time':
                $sort['value'] = 'datetime_input';
                break;
            case 'status':
                $sort['value'] = 'res_reservation_status_id';
                break;
            case 'covers':
                $sort['value'] = 'num_guest';
                break;
            case 'guest':
                $sort['value'] = 'guest.first_name';
                break;
            case 'table':
                $sort['value'] = 'table.name';
                break;
            default:
                $sort['value'] = 'datetime_input';
                break;
        }
        return (object) $sort;
    }

    private function block($microsite_id, $block_id)
    {
        return \App\Entities\Block::where("ms_microsite_id", $microsite_id)->with('tables')->find($block_id);
    }

    private function reservation($microsite_id, $reservation_id)
    {
        $get = array(
            "id",
            "res_guest_id",
            "res_reservation_status_id",
            "date_reservation",
            "hours_reservation",
            "hours_duration",
            "num_guest",
            "note",
            "res_server_id",
        );
        return \App\res_reservation::select($get)->withRelations()->where("ms_microsite_id", $microsite_id)->find($reservation_id);
    }

    private function status()
    {
        return \App\res_reservation_status::where('status', 1)->get();
    }

    private function tagsReservations(int $microsite_id)
    {
        return \App\res_tag_r::where('ms_microsite_id', $microsite_id)->get();
    }

    private function turnsIdsByRangeDate(int $microsite_id, string $date_ini, string $date_end)
    {

        $fecha     = \Carbon\Carbon::parse($date_ini);
        $dayOfWeek = $fecha->dayOfWeek + 1;

        $fechaEnd     = \Carbon\Carbon::parse($date_end);
        $dayOfWeekEnd = $fecha->dayOfWeek + 1;
        /* Obtener Los Ids de los turnos Habilitados para un rabgo de fecha */
        return $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
            ->where("res_turn.ms_microsite_id", $microsite_id)
            ->where(function ($query) use ($fecha, $fechaEnd) {
                return $query->where("start_date", "<=", $fecha->toDateString())->where("end_date", ">=", $fecha->toDateString())
                    ->orWhere("start_date", ">=", $fecha->toDateString())->where("start_date", "<=", $fechaEnd->toDateString());
            })
            ->pluck('id');
    }

    private function turnsIdsByDate(int $microsite_id, string $date)
    {

        $fecha     = \Carbon\Carbon::parse($date);
        $dayOfWeek = $fecha->dayOfWeek + 1;
        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        return $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
            ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
            ->where("res_turn.ms_microsite_id", $microsite_id)
            ->where("start_date", "<=", $fecha->toDateString())
            ->where("end_date", ">=", $fecha->toDateString())
            ->pluck('id');
    }

    private function shifts($turnIds, $notes = null)
    {

        $turns = res_turn::whereIn('id', $turnIds)->with(['zones' => function ($query) {
            return $query->select('id');
        }])->get();

        return \App\res_type_turn::where('status', 1)->get()->map(function ($item) use ($turns, $notes) {
            $item->turn  = $turns->where('res_type_turn_id', $item->id)->first();
            $item->notes = ($notes) ? $notes->where('res_type_turn_id', $item->id)->first() : null;
            return $item;
        });
    }

    private function turnsByIds($turnIds)
    {
        return res_turn::whereIn('id', $turnIds)->with(['typeTurn'])->get();
    }

    private function zonesIdsByTurnsIds($turnIds)
    {
        /* Obtener Los Ids de las zonas Habbilitadas por los turnos habiles */
        $zoneIds = \App\res_turn_zone::whereIn('res_turn_id', $turnIds)->groupBy('res_zone_id')->pluck('res_zone_id');
        return \App\res_zone::whereIn('id', $zoneIds)->with(['tables'])->get();
    }

    private function blocksByRangeDate(int $microsite_id, string $dateTimeOpen, string $dateTimeClose)
    {

        return \App\Entities\Block::with('tables')->where("ms_microsite_id", "=", $microsite_id)->whereRaw("CONCAT(res_block.start_date, ' ', res_block.start_time) BETWEEN ? AND ?", array($dateTimeOpen, $dateTimeClose))->get();
    }

    private function reservationsByDate(int $microsite_id, string $date)
    {
        return \App\res_reservation::select("res.*")->with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList"])->from("res_reservation as res")->where("res.date_reservation", $date)->get();
    }

    private function reservationsByRangeDate(int $microsite_id, string $dateTimeOpen, string $dateTimeClose)
    {
        return \App\res_reservation::select("res.*")->with([
            "tables" => function ($query) {
                return $query->select("res_table.id", "res_zone_id", "name");
            }, "guest", "guest.emails", "guest.phones", "server", "source", "status", "turn.typeTurn", "tags", "guestList"])->from("res_reservation as res")->whereRaw("CONCAT(res.date_reservation, ' ', res.hours_reservation) BETWEEN ? AND ?", array($dateTimeOpen, $dateTimeClose))->get();
    }

    private function configuration(int $microsite_id)
    {
        $configuration = \App\Entities\res_configuration::where("ms_microsite_id", $microsite_id)->with(['forms' => function ($query) {
            $query->where('status', 1);
        }])->first();
        if ($configuration) {
            return $configuration;
        }
        $configuration = new \App\Entities\res_configuration();
        return $configuration->default($microsite_id);
    }

    private function servers(int $microsite_id)
    {
        return \App\Entities\Server::where("ms_microsite_id", $microsite_id)->with(['tables'])->get();
    }

    private function souceTypes()
    {
        return \App\res_source_type::where("status", 1)->get(array("id", "name", "description"));
    }

    private function notes(int $microsite_id, string $date)
    {
        return \App\res_note::where('ms_microsite_id', $microsite_id)->where("date_add", $date)->get();
    }

}
