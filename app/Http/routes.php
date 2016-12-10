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
    //-----------------------------------------------------
    // TIPOS DE ORIGOEN DE RESERVACIONES
    //-----------------------------------------------------
    Route::get("reservation/source-types", "ReservationController@listSourceType");

    routeMesas();
});

function routeMesas()
{

    //-----------------------------------------------------
    // MICROSITE
    //-----------------------------------------------------
    Route::group(['prefix' => 'microsites/{microsite_id}', 'middleware' => ['setLocale', 'setTimeZone', 'ACL:microsite']], function () {

        //-----------------------------------------------------
        // MICROSITE::ZONAS
        //-----------------------------------------------------
        Route::get('zones', ['uses' => 'ZoneController@index']); /* Lista de todas las zonas */
        Route::get('zones/actives', ['uses' => 'ZoneController@getListActives']); /* Lista de zonas activas en alguna fehca en adelante*/
        Route::get('zones/activesByDate', ['uses' => 'ZoneController@getListActivesByDate']); /* Lista de zonas activas en una fecha determinada */
        Route::get('zones/{zone_id}', 'ZoneController@show');
        Route::get('zones/{zone_id}/tables', 'ZoneController@listTable');
        Route::post('zones', 'ZoneController@create');
        Route::put('zones/{zone_id}', 'ZoneController@update');
        Route::delete('zones/{zone_id}', 'ZoneController@delete');

        //-----------------------------------------------------
        // MICROSITE::BLOQUEO
        //-----------------------------------------------------
        Route::get('blocks', 'BlockController@index');
        Route::get('blocks/tables', 'BlockController@getTables');
        Route::get('blocks/{block_id}', 'BlockController@getBlock');
        Route::delete('blocks/{block_id}', 'BlockController@delete');
        Route::post('blocks', 'BlockController@insert');
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
        Route::get('turns', 'TurnController@index');
        Route::get('turns/calendar', 'TurnController@calendar'); /* lista de turnos de una fecha de calnedario */
        Route::get('turns/search', 'TurnController@search');
        //Route::get('turns/{turn_id}/availability', 'TurnController@tableAvailability');
        //Notas del turno
        Route::get('turns/notes', 'NoteController@index');
        Route::post('turns/notes', 'NoteController@create');

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
        // MICROSITE::TABLES
        //-----------------------------------------------------
        Route::get('tables/availability', 'TableController@availability');
        Route::get('tables/searchAvailability', 'TableController@searchAvailability');

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

        //-----------------------------------------------------
        // MICROSITE::RESERVATION
        //-----------------------------------------------------
        Route::get('reservations', 'ReservationController@index');
        Route::get('reservations/search', 'ReservationController@search');
        Route::get('reservations/{reservation_id}', 'ReservationController@show');
        Route::post('reservations', 'ReservationController@create');
        Route::put('reservations/{reservation_id}', 'ReservationController@update');
        Route::patch('reservations/{reservation_id}', 'ReservationController@patch');
        Route::delete('reservations/{reservation_id}', 'ReservationController@delete');
        Route::post('reservations/{reservation_id}/send-email', 'ReservationController@sendEmail');

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION
        //-----------------------------------------------------

        Route::resource('table/reservation', 'TableReservationController', ["only" => ["store", "edit", "update"]]);

        Route::put('table/reservation/{reservation}/cancel', 'TableReservationController@cancel');
        Route::put('table/reservation/{reservation}/quickedit', 'TableReservationController@quickEdit');
        Route::put('table/reservation/{reservation}/sit', 'TableReservationController@sit');
        Route::post('table/reservation/quickcreate', 'TableReservationController@quickCreate');
        Route::put('table/reservation/{reservation}/guest-list', 'TableReservationController@updateGuestList');
        Route::post('table/reservation/w', 'TableReservationController@storeFromWeb');

        Route::post('waitlist', 'TableReservationController@createWaitList');
        Route::put('waitlist', 'TableReservationController@updateWaitList');
        Route::delete('waitlist/{id}', 'TableReservationController@deleteWaitList');

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION TAGS
        //-----------------------------------------------------
        Route::resource("reservation/tag", "ReservationTagController", ["only" => ["index", "store", "destroy"]]);

        //-----------------------------------------------------
        // MICROSITE:: CONFIGURATION (table res_configuration)
        //-----------------------------------------------------
        Route::patch("configuration/reservations", "ConfigurationController@edit");
        Route::resource("configuration/reservations", "ConfigurationController", ["only" => ["index", "update"]]);
        Route::post("configuration/reservations/forms", "ConfigurationController@addFormConfiguration");
        Route::delete("configuration/reservations/forms", "ConfigurationController@removeFormConfiguration");
        Route::get("configuration/reservations/forms", "ConfigurationController@getForm");

        //-----------------------------------------------------
        // MICROSITE:: PERCENTAGE (table res_percentage)
        //----------------------------------------------------

        Route::resource("configuration/percentages", "PercentageController", ["only" => ["index"]]);
        //-----------------------------------------------------
        // MICROSITE:: CODES (table res_code)
        //-----------------------------------------------------
        Route::resource("configuration/codes", "ConfigurationCodeController", ["only" => ["index", "store", "update", "destroy"]]);

        //-----------------------------------------------------
        // MICROSITE:: USER (table bs_user)
        //-----------------------------------------------------
        Route::get("configuration/users/privileges", "ConfigurationUserController@getAllUser");
        Route::resource("configuration/users", "ConfigurationUserController", ["only" => ["index", "destroy", "store"]]);

        //-----------------------------------------------------
        // MICROSITE:: Reservation Temporal
        //-----------------------------------------------------
        Route::resource("reservationtemporal/", "ReservationTemporalController", ["only" => ["index", "destroy", "store"]]);
        Route::get("reservationtemporal/{token}", "ReservationTemporalController@show");

        //-----------------------------------------------------
        // MICROSITE:: Availability
        //-----------------------------------------------------
        Route::group(['prefix' => 'availability/'], function () {
            Route::get('basic', 'AvailabilityController@basic');
            Route::get('zones', 'AvailabilityController@getZones');
            Route::get('hours', 'AvailabilityController@getHours');
            Route::get('events', 'AvailabilityController@getEvents');
            Route::get('days', 'AvailabilityController@getDays');
            Route::get('daysdisabled', 'AvailabilityController@getDaysDisabled');
            Route::get('people', 'AvailabilityController@getPeople');
            Route::get('formatAvailability', 'AvailabilityController@getFormatAvailability');
        });
    });

}
