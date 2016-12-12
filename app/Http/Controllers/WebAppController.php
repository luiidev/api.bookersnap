<?php

namespace App\Http\Controllers;

use App\Events\EmitNotification;
use App\Http\Requests\TurnRequest;
use App\Services\BlockService;
use App\Services\TurnService;
use App\Services\CalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Services\Helpers\CalendarHelper;
use App\res_turn;
use DB;

class WebAppController extends Controller
{

    protected $_TurnService;
    protected $_BlockService;
    protected $_CalendarService;

    public function __construct(TurnService $TurnService, BlockService $BlockService, CalendarService $CalendarService)
    {
        $this->_TurnService = $TurnService;
        $this->_BlockService = $BlockService;
        $this->_CalendarService = $CalendarService;
    }

    public function floor(Request $request)
    {
        return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            $date = CalendarHelper::realDate($microsite_id);
            $dateTimeOpen = CalendarHelper::realDateTimeOpen($microsite_id, $date);
            $dateTimeClose = CalendarHelper::realDateTimeClose($microsite_id, $date);

            $turnsIds = $this->turnsIdsByDate($microsite_id, $date);
            $turns = $this->turnsByIds($turnsIds);
            $zones = $this->zonesIdsByTurnsIds($turnsIds);
            $blocks = $this->blocksByRangeDate($microsite_id, $dateTimeOpen, $dateTimeClose);
            $reservations = $this->reservationsByDate($microsite_id, $date);
            $configuration = $this->configuration($microsite_id);
            $servers = $this->servers($microsite_id);
            $sourceTypes = $this->souceTypes();
            $notes = $this->notes($microsite_id, $date);
            $shifts = $this->shifts($turnsIds, $notes);
            $tags = $this->tagsReservations($microsite_id);
            $status = $this->status();

            $data = [
            	"schedule" => [
            		"dateOpen" => $date,
	            	"datetimeOpen" => $dateTimeOpen,
	            	"datetimeClose" => $dateTimeClose,
            	],
            	"config" => $configuration,
            	"status" => $status,
            	"tags" => $tags,
            	"turns" => $turns,
            	"shifts" => $shifts,
            	"zones" => $zones,
            	"blocks" => $blocks,
            	"reservations" => $reservations,
            	"servers" => $servers,
            	"sourceTypes" => $sourceTypes
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }    
    
    public function editReservation(Request $request){

    	return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            $reservation_id = $request->route('reservation_id');
                        
            $reservation = $this->reservation($microsite_id, $reservation_id);
            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date =  ($reservation)?$reservation->date_reservation : CalendarHelper::realDate($microsite_id);
            }

            
            $turnsIds = $this->turnsIdsByDate($microsite_id, $date);
            $zones = $this->zonesIdsByTurnsIds($turnsIds);
            $configuration = $this->configuration($microsite_id);
            $servers = $this->servers($microsite_id);
            $tags = $this->tagsReservations($microsite_id);
            $sourceTypes = $this->souceTypes();
            $status = $this->status();
            $notes = $this->notes($microsite_id, $date);
            $shifts = $this->shifts($turnsIds, $notes);
            $blockTables = $this->_BlockService->getTables($microsite_id, $date);
            $reservations = $this->reservationsByDate($microsite_id, $date);
            
            $data = [
            	"reservation" => $reservation,
                "zones" => $zones,
            	"config" => $configuration,
                "status" => $status,
            	"servers" => $servers,
            	"tags" => $tags,
                "shifts" => $shifts,
            	"sourceTypes" => $sourceTypes,
                "blockTables" => $blockTables,
                "reservations" => $reservations
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }
            
    public function editBlock(Request $request){

    	return $this->TryCatch(function () use ($request) {

            $microsite_id = $request->route('microsite_id');
            $block_id = $request->route('block_id');
            
            $block = $this->block($microsite_id, $block_id);

            if ($request->has("date")) {
                $date = CalendarHelper::realDate($microsite_id, $request->input('date'));
            } else {
                $date = ($block) ? $block->start_date: CalendarHelper::realDate($microsite_id);
            }
            
            $turnsIds = $this->turnsIdsByDate($microsite_id, $date);
            $zones = $this->zonesIdsByTurnsIds($turnsIds);
            $notes = $this->notes($microsite_id, $date);
            $shifts = $this->shifts($turnsIds, $notes);
            $blockTables = $this->_BlockService->getTables($microsite_id, $date);
            $data = [
                "block" => $block,
                "zones" => $zones,
                "shifts" => $shifts,
                "blockTables" => $blockTables,
            ];
            return $this->CreateResponse(true, 201, "", $data);
        });
    }
    
    private function block($microsite_id, $block_id) {
        return \App\Entities\Block::where("ms_microsite_id", $microsite_id)->with('tables')->find($block_id);
    }
    
    private function reservation($microsite_id, $reservation_id) {
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

    private function status(){
    	return \App\res_reservation_status::where('status', 1)->get();
    }
    private function tagsReservations(int $microsite_id){
    	return \App\res_tag_r::where('ms_microsite_id', $microsite_id)->get();
    }

    private function turnsIdsByDate(int $microsite_id, string $date) {

        $fecha = \Carbon\Carbon::parse($date);
        $dayOfWeek = $fecha->dayOfWeek + 1;
        /* Obtener Los Ids de los turnos Habilitados para la fecha */
        return $turnsIds = \App\res_turn_calendar::join("res_turn", "res_turn.id", "=", "res_turn_calendar.res_turn_id")
                ->where(DB::raw("dayofweek(start_date)"), $dayOfWeek)
                ->where("res_turn.ms_microsite_id", $microsite_id)
                ->where("start_date", "<=", $fecha->toDateString())
                ->where("end_date", ">=", $fecha->toDateString())
                ->pluck('id');
    }

    private function shifts($turnIds, $notes = null) {

    	$turns = res_turn::whereIn('id', $turnIds)->with(['zones' => function($query){
    		return $query->select('id');
    	}])->get();

        return \App\res_type_turn::where('status', 1)->get()->map(function($item) use($turns, $notes){
        	$item->turn = $turns->where('res_type_turn_id', $item->id)->first();
        	$item->notes = ($notes)? $notes->where('res_type_turn_id', $item->id)->first(): null;
        	return $item;
        });
    }

    private function turnsByIds($turnIds){
    	return res_turn::whereIn('id', $turnIds)->with(['typeTurn'])->get();
    }

    private function zonesIdsByTurnsIds($turnIds){
    	/* Obtener Los Ids de las zonas Habbilitadas por los turnos habiles */
        $zoneIds = \App\res_turn_zone::whereIn('res_turn_id', $turnIds)->groupBy('res_zone_id')->pluck('res_zone_id');
        return \App\res_zone::whereIn('id',$zoneIds)->with(['tables'])->get();
    }

    private function blocksByRangeDate(int $microsite_id, string $dateTimeOpen, string $dateTimeClose) {
        
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

    private function configuration(int $microsite_id){
    	$configuration = \App\Entities\res_configuration::where("ms_microsite_id", $microsite_id)->with(['forms' => function ($query) {
            $query->where('status', 1);
        }])->first();
        if($configuration){
        	return $configuration;
        }
        $configuration = new \App\Entities\res_configuration();
        return $configuration->default($microsite_id);
    }

    private function servers(int $microsite_id){
    	return \App\Entities\Server::where("ms_microsite_id", $microsite_id)->with(['tables'])->get();
    }

    private function souceTypes(){
    	return \App\res_source_type::where("status", 1)->get(array("id", "name", "description"));
    }

    private function notes(int $microsite_id, string $date){
    	return \App\res_note::where('ms_microsite_id', $microsite_id)->where("date_add", $date)->get();
    }
}