<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|s
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
        Route::get('zones', ['uses' => 'ZoneController@index', 'middleware' =>'ACL:adminms-table-zone-index' ]); /* Lista de todas las zonas */
        Route::get('zones/actives', ['uses' => 'ZoneController@getListActives', 'middleware' =>'ACL:adminms-table-zone-getListActives']); /* Lista de zonas activas en alguna fehca en adelante*/
        Route::get('zones/activesByDate', ['uses' => 'ZoneController@getListActivesByDate', 'middleware' =>'ACL:adminms-table-zone-getListActivesByDate']); /* Lista de zonas activas en una fecha determinada */
        Route::get('zones/{zone_id}', 'ZoneController@show', [ 'middleware' =>'ACL:adminms-table-zone-show' ]);
        Route::get('zones/{zone_id}/tables', 'ZoneController@listTable', [ 'middleware' =>'ACL:adminms-table-zone-listTable' ]);
        Route::post('zones', 'ZoneController@create', [ 'middleware' =>'ACL:adminms-table-zone-create' ]);
        Route::put('zones/{zone_id}', 'ZoneController@update', [ 'middleware' =>'ACL:adminms-table-zone-update' ]);
        Route::delete('zones/{zone_id}', 'ZoneController@delete', [ 'middleware' =>'ACL:adminms-table-zone-delete' ]);

        //-----------------------------------------------------
        // MICROSITE::BLOQUEO
        //-----------------------------------------------------
        Route::get('blocks', 'BlockController@index', [ 'middleware' =>'ACL:adminms-table-block-index' ]);
        Route::get('blocks/tables', 'BlockController@getTables', [ 'middleware' =>'ACL:adminms-table-block-getTables' ]);
        Route::get('blocks/{block_id}', 'BlockController@getBlock', [ 'middleware' =>'ACL:adminms-table-block-getBlock' ]);
        Route::delete('blocks/{block_id}', 'BlockController@delete', [ 'middleware' =>'ACL:adminms-table-block-delete' ]);
        Route::post('blocks', 'BlockController@insert', [ 'middleware' =>'ACL:adminms-table-block-insert' ]);
        Route::put('blocks/{block_id}', 'BlockController@update', [ 'middleware' =>'ACL:adminms-table-block-update' ]);
        Route::patch('blocks/{block_id}/grid', 'BlockController@updateGrid', [ 'middleware' =>'ACL:adminms-table-block-updateGrid' ]);

        //-----------------------------------------------------
        // MICROSITE::SERVERS
        //-----------------------------------------------------
        Route::get('servers', 'ServerController@listado', [ 'middleware' =>'ACL:adminms-table-server-listado' ]);
        Route::post('servers', 'ServerController@insert', [ 'middleware' =>'ACL:adminms-table-server-insert' ]);
        Route::put('servers/{server_id}', 'ServerController@update', [ 'middleware' =>'ACL:adminms-table-server-update' ]);
        Route::delete('servers/{server_id}', 'ServerController@delete', [ 'middleware' =>'ACL:adminms-table-server-delete' ]);

        //-----------------------------------------------------
        // MICROSITE::TURNOS
        //-----------------------------------------------------
        Route::get('turns', 'TurnController@index', [ 'middleware' =>'ACL:adminms-table-turn-index' ]);
        Route::get('turns/calendar', 'TurnController@calendar', [ 'middleware' =>'ACL:adminms-table-turn-calendar' ]); /* lista de turnos de una fecha de calnedario */
        Route::get('turns/search', 'TurnController@search', [ 'middleware' =>'ACL:adminms-table-turn-search' ]);
        //Route::get('turns/{turn_id}/availability', 'TurnController@tableAvailability');
        //Notas del turno
        Route::get('turns/notes', 'NoteController@index', [ 'middleware' =>'ACL:adminms-table-turnNote-index' ]);
        Route::post('turns/notes', 'NoteController@create', [ 'middleware' =>'ACL:adminms-table-turnNote-create' ]);

        Route::get('turns/{turn_id}', 'TurnController@show', [ 'middleware' =>'ACL:adminms-table-turn-show' ]);
        Route::delete('turns/{turn_id}', 'TurnController@delete', [ 'middleware' =>'ACL:adminms-table-turn-delete' ]);
        Route::post('turns/', 'TurnController@create', [ 'middleware' =>'ACL:adminms-table-turn-create' ]);
        Route::put('turns/{turn_id}', 'TurnController@update', [ 'middleware' =>'ACL:adminms-table-turn-update' ]);
        Route::get('turns/{turn_id}/unlink-zones/{zone_id}', 'TurnController@unlinkZone', [ 'middleware' =>'ACL:adminms-table-turn-unlinkZone' ]);
        Route::get('turns/{turn_id}/zones/{zone_id}/tables', 'TurnController@listTableZone', [ 'middleware' =>'ACL:adminms-table-turn-listTableZone' ]);

        //-----------------------------------------------------
        // MICROSITE::CALENDAR
        //-----------------------------------------------------
        Route::get('calendar/{date}', 'CalendarController@index', [ 'middleware' =>'ACL:adminms-table-calendar-index' ]);
        Route::get('calendar/{date}/zones', 'CalendarController@getZones', [ 'middleware' =>'ACL:adminms-table-calendar-getZones' ]);
        Route::get('calendar/{date}/shifts', 'CalendarController@listShift', [ 'middleware' =>'ACL:adminms-table-calendar-listShift' ]);

        Route::post('calendar', 'CalendarController@storeCalendar', [ 'middleware' =>'ACL:adminms-table-calendar-storeCalendar' ]);
        Route::delete('calendar/{res_turn_id}', 'CalendarController@deleteCalendar', [ 'middleware' =>'ACL:adminms-table-calendar-deleteCalendar' ]);
        Route::put('calendar/change', 'CalendarController@changeCalendar', [ 'middleware' =>'ACL:adminms-table-calendar-changeCalendar' ]);

        Route::get('calendar/{turn_id}/{start_time}/{end_time}', 'CalendarController@existConflictTurn', [ 'middleware' =>'ACL:existConflictTurn' ]);

        //-----------------------------------------------------
        // MICROSITE::TABLES
        //-----------------------------------------------------
        Route::get('tables/availability', 'TableController@availability');
        Route::get('tables/searchAvailability', 'TableController@searchAvailability');

        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES
        //-----------------------------------------------------
        Route::get('guests', 'GuestController@index', [ 'middleware' =>'ACL:adminms-table-guest-index' ]);
        Route::get('guests/tags', 'GuestTagCategoryController@index', [ 'middleware' =>'ACL:adminms-table-guestTag-index' ]);
        Route::get('guests/{guest_id}', 'GuestController@show', [ 'middleware' =>'ACL:adminms-table-guest-show' ]);
        Route::post('guests', 'GuestController@create', [ 'middleware' =>'ACL:adminms-table-guest-create' ]);
        Route::put('guests/{guest_id}', 'GuestController@update', [ 'middleware' =>'ACL:adminms-table-guest-update' ]);
        Route::get('guests/{guest_id}/reservations', 'GuestController@reservation', [ 'middleware' =>'ACL:adminms-table-guest-reservation' ]);

        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES TAGS
        //-----------------------------------------------------
        Route::get('guest-tags/', 'GuestController@listGuestTag', [ 'middleware' =>'ACL:adminms-table-guestTag-listGuestTag' ]);
        Route::post('guest-tags/', 'GuestController@createGuestTag', [ 'middleware' =>'ACL:adminms-table-guestTag-createGuestTag' ]);
        Route::delete('guest-tags/{guest_tag_id}', 'GuestController@deleteGuestTag', [ 'middleware' =>'ACL:adminms-table-guestTag-deleteGuestTag' ]);

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TURNS
        //-----------------------------------------------------
        Route::get('zones/{zone_id}/turns', 'ZoneTurnController@index', [ 'middleware' =>'ACL:adminms-table-zoneTurn-index' ]);
        Route::get('zones/{zone_id}/turns/{id}', 'ZoneTurnController@show', [ 'middleware' =>'ACL:adminms-table-zoneTurn-show' ]);
        Route::post('zones/{zone_id}/turns', 'ZoneTurnController@create', [ 'middleware' =>'ACL:adminms-table-zoneTurn-create' ]);
        Route::put('zones/{zone_id}/turns/{id}', 'ZoneTurnController@update', [ 'middleware' =>'ACL:adminms-table-zoneTurn-update' ]);
        Route::delete('zones/{zone_id}/turns/{id}', 'ZoneTurnController@delete', [ 'middleware' =>'ACL:adminms-table-zoneTurn-delete' ]);

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TYPETURNS::DAYS
        //-----------------------------------------------------
        Route::get('zones/{zone_id}/type-turns/{id}/days', 'ZoneTypeturnController@index', [ 'middleware' =>'ACL:adminms-table-zoneTypeTurn-index' ]);
        Route::get('zones/{zone_id}/type-turns/{id}/days/available', 'ZoneTypeturnController@available', [ 'middleware' =>'ACL:adminms-table-zoneTypeTurn-available' ]);

        //-----------------------------------------------------
        // MICROSITE::RESERVATION
        //-----------------------------------------------------
        Route::get('reservations', 'ReservationController@index', [ 'middleware' =>'ACL:adminms-table-reservation-index' ]);
        Route::get('reservations/{reservation_id}', 'ReservationController@show', [ 'middleware' =>'ACL:adminms-table-reservation-show' ]);
        Route::post('reservations', 'ReservationController@create', [ 'middleware' =>'ACL:adminms-table-reservation-create' ]);
        Route::put('reservations/{reservation_id}', 'ReservationController@update', [ 'middleware' =>'ACL:adminms-table-reservation-update' ]);
        Route::patch('reservations/{reservation_id}', 'ReservationController@patch', [ 'middleware' =>'ACL:adminms-table-reservation-patch' ]);
        Route::delete('reservations/{reservation_id}', 'ReservationController@delete', [ 'middleware' =>'ACL:adminms-table-reservation-delete' ]);
        Route::post('reservations/{reservation_id}/send-email', 'ReservationController@sendEmail', [ 'middleware' =>'ACL:adminms-table-reservation-sendEmail' ]);
        Route::post('reservations/{reservation_id}/grid', 'ReservationController@updateGrid', [ 'middleware' =>'ACL:adminms-table-reservation-updateGrid' ]);

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION
        //-----------------------------------------------------

        // Route::resource('table/reservation', 'TableReservationController', ["only" => ["store", "edit", "update"]]);

        Route::post('table/reservation', 'TableReservationController@store', [ 'middleware' =>'ACL:adminms-table-tableReservation-store' ]);
        Route::get('table/reservation/{reservation}/edit', 'TableReservationController@edit', [ 'middleware' =>'ACL:adminms-table-tableReservation-edit' ]);
        Route::put('table/reservation/{reservation}', 'TableReservationController@update', [ 'middleware' =>'ACL:adminms-table-tableReservation-update' ]);
        Route::put('table/reservation/{reservation}/cancel', 'TableReservationController@cancel', [ 'middleware' =>'ACL:adminms-table-tableReservation-cancel' ]);
        Route::put('table/reservation/{reservation}/quickedit', 'TableReservationController@quickEdit', [ 'middleware' =>'ACL:adminms-table-tableReservation-quickEdit' ]);
        Route::put('table/reservation/{reservation}/sit', 'TableReservationController@sit', [ 'middleware' =>'ACL:adminms-table-tableReservation-sit' ]);
        Route::post('table/reservation/quickcreate', 'TableReservationController@quickCreate', [ 'middleware' =>'ACL:adminms-table-tableReservation-quickCreate' ]);
        Route::put('table/reservation/{reservation}/guest-list', 'TableReservationController@updateGuestList', [ 'middleware' =>'ACL:adminms-table-tableReservation-updateGuestList' ]);

        Route::group(['middleware' => ['auth.api']], function () {
            Route::post('table/reservation/w', 'TableReservationController@storeFromWeb');
            Route::get('table/reservation/confirmed/{crypt}', 'TableReservationController@showByCrypt');
            Route::post('table/reservation/cancel/{crypt}', 'TableReservationController@cancelReserveWeb');
        });

        Route::post('waitlist', 'TableReservationController@createWaitList', ['middleware' => 'adminms-table-waitlist-createWaitList']);
        Route::put('waitlist', 'TableReservationController@updateWaitList', ['middleware' => 'adminms-table-waitlist-updateWaitList']);
        Route::delete('waitlist/{id}', 'TableReservationController@deleteWaitList', ['middleware' => 'adminms-table-waitlist-deleteWaitList']);

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION TAGS
        //-----------------------------------------------------
        // Route::resource("reservation/tag", "ReservationTagController", ["only" => ["index", "store", "destroy"]]);

        Route::get('reservation/tag', 'ReservationTagController@index', ['middleware' => 'adminms-table-reservationTag-index']);
        Route::post('reservation/tag', 'ReservationTagController@store', ['middleware' => 'adminms-table-reservationTag-store']);
        Route::delete('reservation/tag/{tag}', 'ReservationTagController@destroy', ['middleware' => 'adminms-table-reservationTag-destroy']);

        //-----------------------------------------------------
        // MICROSITE:: CONFIGURATION (table res_configuration)
        //-----------------------------------------------------
        Route::patch("configuration/reservations", "ConfigurationController@edit", [ 'middleware' =>'ACL:adminms-table-configurationRes-edit' ]);
        // Route::resource("configuration/reservations", "ConfigurationController", ["only" => ["index", "update"]]);
        Route::get("configuration/reservations", "ConfigurationController@index", [ 'middleware' =>'ACL:adminms-table-configurationRes-index' ]);
        Route::put("configuration/reservations/{reservation}", "ConfigurationController@update", [ 'middleware' =>'ACL:adminms-table-configurationRes-update' ]);
        Route::post("configuration/reservations/forms", "ConfigurationController@addFormConfiguration", [ 'middleware' =>'ACL:adminms-table-configurationRes-addFormConfiguration' ]);
        Route::delete("configuration/reservations/forms", "ConfigurationController@removeFormConfiguration", [ 'middleware' =>'ACL:adminms-table-configurationRes-removeFormConfiguration' ]);
        Route::get("configuration/reservations/forms", "ConfigurationController@getForm", [ 'middleware' =>'ACL:adminms-table-configurationRes-getForm' ]);

        //-----------------------------------------------------
        // MICROSITE:: PERCENTAGE (table res_percentage)
        //----------------------------------------------------

        // Route::resource("configuration/percentages", "PercentageController", ["only" => ["index"]]);
        Route::get("configuration/percentages", "PercentageController@index", [ 'middleware' =>'ACL:adminms-table-configurationPer-index' ]);

        //-----------------------------------------------------
        // MICROSITE:: CODES (table res_code)
        //-----------------------------------------------------
        // Route::resource("configuration/codes", "ConfigurationCodeController", ["only" => ["index", "store", "update", "destroy"]]);
        Route::get("configuration/codes", "ConfigurationCodeController@index", [ 'middleware' =>'ACL:adminms-table-configurationCodes-index' ]);
        Route::post("configuration/codes", "ConfigurationCodeController@store", [ 'middleware' =>'ACL:adminms-table-configurationCodes-store' ]);
        Route::put("configuration/codes/{code}", "ConfigurationCodeController@update", [ 'middleware' =>'ACL:adminms-table-configurationCodes-update' ]);
        Route::delete("configuration/codes/{code}", "ConfigurationCodeController@destroy", [ 'middleware' =>'ACL:adminms-table-configurationCodes-destroy' ]);

        //-----------------------------------------------------
        // MICROSITE:: USER (table bs_user)
        //-----------------------------------------------------
        Route::get("configuration/users/privileges", "ConfigurationUserController@getAllUser", [ 'middleware' =>'ACL:adminms-table-configurationUserPriv-getAllUser' ]);
        // Route::resource("configuration/users", "ConfigurationUserController", ["only" => ["index", "destroy", "store"]]);
        Route::get("configuration/users", "ConfigurationUserController@index", ["middleware" => 'ACL:adminms-table-configurationUser-index']);
        Route::delete("configuration/users/{user}", "ConfigurationUserController@destroy", ["middleware" => 'ACL:adminms-table-configurationUser-destroy']);
        Route::post("configuration/users/{user}", "ConfigurationUserController@store", ["middleware" => 'ACL:adminms-table-configurationUser-store']);

        //-----------------------------------------------------
        // MICROSITE:: Reservation Temporal
        //-----------------------------------------------------
        Route::group(['prefix' => 'reservationtemporal', 'middleware' => ['auth.api']], function () {
            Route::resource("/", "ReservationTemporalController", ["only" => ["index", "destroy", "store"]]);
            Route::get("/expire", "ReservationTemporalController@expire");
            Route::get("/{token}", "ReservationTemporalController@show");
            Route::delete("/{token}", "ReservationTemporalController@destroy");
        });

        //-----------------------------------------------------
        // MICROSITE:: Availability
        //-----------------------------------------------------
        Route::get('availability/hours', 'AvailabilityController@getHours');
        Route::group(['prefix' => 'availability/', 'middleware' => ['auth.api']], function () {
            Route::get('basic', 'AvailabilityController@basic');
            Route::get('zones', 'AvailabilityController@getZones');

            Route::get('events', 'AvailabilityController@getEvents');
            Route::get('days', 'AvailabilityController@getDays');
            Route::get('daysdisabled', 'AvailabilityController@getDaysDisabled');
            Route::get('days/disabled', 'AvailabilityController@getDaysDisabled');
            Route::get('people', 'AvailabilityController@getPeople');
            Route::get('formatAvailability', 'AvailabilityController@getFormatAvailability');
        });

        //-----------------------------------------------------
        // MICROSITE:: Floor
        //-----------------------------------------------------
        Route::group(['prefix' => 'web-app/'], function () {
            Route::get('floor', 'WebAppController@floor', [ 'middleware' =>'ACL:action' ]);
            Route::get('grid', 'WebAppController@grid', [ 'middleware' =>'ACL:action' ]);
            Route::get('book', 'WebAppController@book', [ 'middleware' =>'ACL:action' ]);
            Route::get('book/history', 'WebAppController@bookHistory', [ 'middleware' =>'ACL:action' ]);
            Route::get('book/history/reservations', 'WebAppController@bookHistoryReservations', [ 'middleware' =>'ACL:action' ]);
            Route::get('reservation', 'WebAppController@editReservation', [ 'middleware' =>'ACL:action' ]);
            Route::get('reservation/{reservation_id}', 'WebAppController@editReservation', [ 'middleware' =>'ACL:action' ]);
            Route::get('block', 'WebAppController@editBlock', [ 'middleware' =>'ACL:action' ]);
            Route::get('block/{block_id}', 'WebAppController@editBlock', [ 'middleware' =>'ACL:action' ]);
        });

        Route::get("notification", "NotificationController@index", [ 'middleware' =>'ACL:adminms-table-notification-index' ]);
        Route::put("notification", "NotificationController@update", [ 'middleware' =>'ACL:adminms-table-notification-update' ]);
    });

}
