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

Route::group(['prefix' => 'v1/{lang}',], function() {
    routeMesas();
});

function routeMesas() {
    
    //-----------------------------------------------------
    // MICROSITE
    //-----------------------------------------------------
    Route::group(['prefix' => 'microsites/{micrositeId}','middleware' => 'responseHeader'], function() {

        //-----------------------------------------------------
        // MICROSITE::ZONAS
        //-----------------------------------------------------
        Route::get('zones/', 'ZoneController@index');
        Route::get('zones/{id}', 'ZoneController@show');
        Route::post('zones/', 'ZoneController@store');
        Route::put('zones/{id}', 'ZoneController@update');
        Route::delete('zones/{id}', 'ZoneController@delete');
        
        //-----------------------------------------------------
        // MICROSITE::ZONAS
        //-----------------------------------------------------
        Route::get('turn/', 'TurnController@index');
        Route::get('turn/{id}', 'TurnController@show');
        Route::post('turn/', 'TurnController@store');
        Route::put('turn/{id}', 'TurnController@update');
        Route::delete('turn/{id}', 'TurnController@delete');
        

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TURNS
        //-----------------------------------------------------
        Route::get('zones/{zone_id}/turns', 'ZoneTurnController@index');
        Route::get('zones/{zone_id}/turns/{id}', 'ZoneTurnController@show');
        Route::post('zones/{zone_id}/turns', 'ZoneTurnController@store');
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
//        Route::get('reservations', 'ConfigZoneTypeturnDayController@available');
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
