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

Route::group(['prefix' => 'v1/{lang}',], function () {
    Route::get('guests-tags-categories', 'GuestTagCategoryController@index');
    routeMesas();
});

function routeMesas()
{


    //-----------------------------------------------------
    // MICROSITE
    //-----------------------------------------------------
    Route::group(['prefix' => 'microsites/{microsite_id}', 'middleware' => ['cors', 'setLocale', 'ACL:microsite']], function () {

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
        //Route::get('zones/{zone_id}/tables', 'ZoneController@listTable');
        //Route::post('zones/', 'ZoneController@create');        
        //Route::put('zones/{zone_id}', 'ZoneController@update');
        //Route::delete('zones/{zone_id}', 'ZoneController@delete');

        //-----------------------------------------------------
        // MICROSITE::TURNOS
        //-----------------------------------------------------
        Route::get('turns/', 'TurnController@index');
        Route::get('turns/search/', 'TurnController@search');
        //Route::get('turns/{turn_id}/availability', 'TurnController@tableAvailability');
        Route::get('turns/{turn_id}', 'TurnController@show');
        Route::post('turns/', 'TurnController@create');
        Route::put('turns/{turn_id}', 'TurnController@update');
        Route::get('turns/{turn_id}/unlink-zones/{zone_id}', 'TurnController@unlinkZone');
        Route::get('turns/{turn_id}/zones/{zone_id}/tables', 'TurnController@listTableZone');

        //-----------------------------------------------------
        // MICROSITE::CALENDAR
        //-----------------------------------------------------
        Route::get('calendar/{date}', 'CalendarController@index');
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

//        Route::get('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
//        Route::post('reservations', 'ConfigZoneTypeturnDayController@available');
//        Route::put('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
//        Route::delete('reservations/{reservation_id}', 'ConfigZoneTypeturnDayController@available');
//        
//        Route::get('days/{day_id}', 'ZoneTypeturnDayController@available');
    });
    //-----------------------------------------------------
    // TYPETURNS
    //-----------------------------------------------------
    Route::get('type-turns', 'TypeTurnController@index');
}
