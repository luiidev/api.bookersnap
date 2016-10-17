<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
 */

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return "documentacion del API";
});

Route::group(['prefix' => 'v1/{lang}', 'middleware' => ['cors']], function () {
    Route::get('guests-tags-categories', 'GuestTagCategoryController@index');
    //-----------------------------------------------------
    // TYPETURNS
    //-----------------------------------------------------
    Route::get('type-turns', 'TypeTurnController@index');
    //-----------------------------------------------------
    // ESTADOS DE RESERVACIONES
    //-----------------------------------------------------
    Route::get("reservation/status", "ReservationController@listStatus");

    routeMesas();
});

function routeMesas()
{

    //-----------------------------------------------------
    // MICROSITE
    //-----------------------------------------------------
    Route::group(['prefix' => 'microsites/{microsite_id}', 'middleware' => ['setLocale', 'ACL:microsite']], function () {

        //-----------------------------------------------------
        // MICROSITE::ZONAS
        //-----------------------------------------------------
        Route::get('zones/', ['uses' => 'ZoneController@index']);
        Route::get('zones/{zone_id}', 'ZoneController@show');
        Route::get('zones/{zone_id}/tables', 'ZoneController@listTable');
        Route::post('zones/', 'ZoneController@create');
        Route::put('zones/{zone_id}', 'ZoneController@update');
        Route::delete('zones/{zone_id}', 'ZoneController@delete');

        //-----------------------------------------------------
        // MICROSITE::BLOQUEO
        //-----------------------------------------------------
        Route::delete('blocks/{block_id}', 'BlockController@delete');
        Route::post('blocks', 'BlockController@insert');
        Route::get('blocks', 'BlockController@list');
        Route::get('blocks/tables', 'BlockController@getTables');
        Route::get('blocks/{block_id}', 'BlockController@getBlock');
        Route::put('blocks/{block_id}', 'BlockController@update');

        //-----------------------------------------------------
        // MICROSITE::SERVERS
        //-----------------------------------------------------
        Route::get('servers', 'ServerController@listado');
        Route::post('servers', 'ServerController@insert');
        Route::put('servers/{server_id}', 'ServerController@update');
        Route::delete('servers/{server_id}', 'ServerController@delete');

        //-----------------------------------------------------
        // MICROSITE::TURNOS
        //-----------------------------------------------------
        Route::get('turns/', 'TurnController@index');
        Route::get('turns/search/', 'TurnController@search');
        //Route::get('turns/{turn_id}/availability', 'TurnController@tableAvailability');
        Route::get('turns/{turn_id}', 'TurnController@show');
        Route::delete('turns/{turn_id}', 'TurnController@delete');
        Route::post('turns/', 'TurnController@create');
        Route::put('turns/{turn_id}', 'TurnController@update');
        Route::get('turns/{turn_id}/unlink-zones/{zone_id}', 'TurnController@unlinkZone');
        Route::get('turns/{turn_id}/zones/{zone_id}/tables', 'TurnController@listTableZone');

        //-----------------------------------------------------
        // MICROSITE::CALENDAR
        //-----------------------------------------------------
        Route::get('calendar/{date}', 'CalendarController@index');
        Route::get('calendar/{date}/zones', 'CalendarController@getZones');
        Route::get('calendar/{date}/shifts', 'CalendarController@listShift');

        Route::post('calendar', 'CalendarController@storeCalendar');
        Route::delete('calendar/{res_turn_id}', 'CalendarController@deleteCalendar');
        Route::put('calendar/change', 'CalendarController@changeCalendar');

        Route::get('calendar/{turn_id}/{start_time}/{end_time}', 'CalendarController@existConflictTurn');

        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES
        //-----------------------------------------------------
        Route::get('guests', 'GuestController@index');
        Route::get('guests/tags', 'GuestTagCategoryController@index');
        Route::get('guests/{guest_id}', 'GuestController@show');
        Route::post('guests', 'GuestController@create');
        Route::put('guests/{guest_id}', 'GuestController@update');
        Route::get('guests/{guest_id}/reservations', 'GuestController@reservation');

        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES TAGS
        //-----------------------------------------------------
        Route::get('guest-tags/', 'GuestController@listGuestTag');
        Route::post('guest-tags/', 'GuestController@createGuestTag');
        Route::delete('guest-tags/{guest_tag_id}', 'GuestController@deleteGuestTag');

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TURNS
        //-----------------------------------------------------
        Route::get('zones/{zone_id}/turns', 'ZoneTurnController@index');
        Route::get('zones/{zone_id}/turns/{id}', 'ZoneTurnController@show');
        Route::post('zones/{zone_id}/turns', 'ZoneTurnController@create');
        Route::put('zones/{zone_id}/turns/{id}', 'ZoneTurnController@update');
        Route::delete('zones/{zone_id}/turns/{id}', 'ZoneTurnController@delete');

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TYPETURNS::DAYS
        //-----------------------------------------------------
        Route::get('zones/{zone_id}/type-turns/{id}/days', 'ZoneTypeturnController@index');
        Route::get('zones/{zone_id}/type-turns/{id}/days/available', 'ZoneTypeturnController@available');

//        Route::get('reservations', 'ZoneTypeturnDayController@available');
        //-----------------------------------------------------
        // MICROSITE::BLOCK
        //-----------------------------------------------------
        //        Route::get('blocks', 'ConfigZoneTypeturnDayController@available');
        //        Route::get('blocks/{block_id}', 'ConfigZoneTypeturnDayController@available');
        //        Route::post('blocks', 'ConfigZoneTypeturnDayController@available');
        //        Route::put('blocks/{block_id}', 'ConfigZoneTypeturnDayController@available');
        //        Route::delete('blocks/{block_id}', 'ConfigZoneTypeturnDayController@available');
        //
        //
        //        Route::get('schedules', 'ConfigZoneTypeturnDayController@available');
        //        Route::get('servers', 'ConfigZoneTypeturnDayController@available');
        //
        //
        Route::get('reservations', 'ReservationController@index');
        Route::post('reservations', 'ReservationController@create');
        Route::put('reservations/{reservation_id}', 'ReservationController@update');
        Route::delete('reservations/{reservation_id}', 'ReservationController@delete');

//        Route::get('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
        //        Route::post('reservations', 'ConfigZoneTypeturnDayController@available');
        //        Route::put('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
        //        Route::delete('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
        //
        //        Route::get('days/{day_id}', 'ZoneTypeturnDayController@available');

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION
        //-----------------------------------------------------
        Route::resource('table/reservation', 'TableReservationController', ["only" => ["store", "edit", "update"]]);
        Route::put('table/reservation/{reservation}/cancel', 'TableReservationController@cancel');
        Route::put('table/reservation/{reservation}/quickedit', 'TableReservationController@quickEdit');
        Route::put('table/reservation/{reservation}/sit', 'TableReservationController@sit');
        Route::post('table/reservation/quickcreate', 'TableReservationController@quickCreate');

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION TAGS
        //-----------------------------------------------------
        Route::resource("reservation/tag", "ReservationTagController", ["only" => ["index", "store"]]);
        // Route::delete('reservation/tag/{tag}', 'ReservationTagController@destroy');

        //-----------------------------------------------------
        // MICROSITE:: CONFIGURATION (table res_configuration)
        //-----------------------------------------------------
        Route::resource("configuration/reservations", "ConfigurationController", ["only" => ["index", "update"]]);
        Route::put("configuration/reservation/codes/status", "ConfigurationController@updateCodeStatus");
        //-----------------------------------------------------
        // MICROSITE:: PERCENTAGE (table res_percentage)
        //-----------------------------------------------------

        //-----------------------------------------------------
        // MICROSITE:: NOTES
        //-----------------------------------------------------
        Route::get('notes/{date}', 'NoteController@index');
        Route::post('notes', 'NoteController@create');

        Route::resource("configuration/percentages", "PercentageController", ["only" => ["index"]]);
        //         //-----------------------------------------------------
        //         // MICROSITE:: CODES (table res_code)
        //         //-----------------------------------------------------
        Route::resource("configuration/codes", "ConfigurationCodeController", ["only" => ["index", "store", "update", "destroy"]]);

    });

}
