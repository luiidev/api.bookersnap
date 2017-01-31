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
    return App\Services\FormService::make()->getFormsByMicrosite(1);
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
    Route::group(['prefix' => 'microsites/{microsite_id}', 'middleware' => ['setLocale', 'setTimeZone', "auth"]], function () {

        //-----------------------------------------------------
        // MICROSITE::ZONAS
        //-----------------------------------------------------
        
        Route::group(["middleware" =>'ACL:adminms-table-zone-show' ], function() {
            Route::get('zones',  'ZoneController@index'); /* Lista de todas las zonas */
            Route::get('zones/actives',  'ZoneController@getListActives'); /* Lista de zonas activas en alguna fehca en adelante*/
            Route::get('zones/activesByDate',  'ZoneController@getListActivesByDate'); /* Lista de zonas activas en una fecha determinada */
            Route::get('zones/{zone_id}',  'ZoneController@show');
            Route::get('zones/{zone_id}/tables',  'ZoneController@listTable');
        });
        Route::post('zones', 'ZoneController@create')->middleware("ACL:adminms-table-zone-store");
        Route::put('zones/{zone_id}', 'ZoneController@update')->middleware("ACL:adminms-table-zone-update");
        Route::delete('zones/{zone_id}', 'ZoneController@delete')->middleware("ACL:adminms-table-zone-delete");

        //-----------------------------------------------------
        // MICROSITE::BLOQUEO
        //-----------------------------------------------------
        Route::group(["middleware" =>'ACL:adminms-table-block-show' ], function() {
            Route::get('blocks', 'BlockController@index');
            Route::get('blocks/tables', 'BlockController@getTables');
            Route::get('blocks/{block_id}', 'BlockController@getBlock');
        });
        Route::group(["middleware" =>'ACL:adminms-table-block-update' ], function() {
            Route::put('blocks/{block_id}', 'BlockController@update');
            Route::patch('blocks/{block_id}/grid', 'BlockController@updateGrid');
        });
        Route::delete('blocks/{block_id}', 'BlockController@delete')->middleware("ACL:adminms-table-block-delete");
        Route::post('blocks', 'BlockController@insert')->middleware("ACL:adminms-table-block-store");


        //-----------------------------------------------------
        // MICROSITE::SERVERS
        //-----------------------------------------------------
        Route::get('servers', 'ServerController@listado')->middleware("ACL:adminms-table-server-show");
        Route::post('servers', 'ServerController@insert')->middleware("ACL:adminms-table-server-store");
        Route::put('servers/{server_id}', 'ServerController@update')->middleware("ACL:adminms-table-server-update");
        Route::delete('servers/{server_id}', 'ServerController@delete')->middleware("ACL:adminms-table-server-delete");

        
        //-----------------------------------------------------
        // MICROSITE::TURNOS
        //-----------------------------------------------------
        //
        //Notas del turno
        Route::get('turns/notes', 'NoteController@index')->middleware("ACL:adminms-table-turnNote-index"); //se esta usando en aplicacion de grid
        Route::post('turns/notes', 'NoteController@create')->middleware("ACL:adminms-table-turnNote-create");
        
        Route::group(["middleware" =>'ACL:adminms-table-turn-show' ], function() {
            Route::get('turns', 'TurnController@index');
            Route::get('turns/calendar', 'TurnController@calendar'); /* lista de turnos de una fecha de calnedario */
            Route::get('turns/search', 'TurnController@search');
            Route::get('turns/{turn_id}', 'TurnController@show');
            Route::get('turns/{turn_id}/zones/{zone_id}/tables', 'TurnController@listTableZone');
        });        
        Route::post('turns/', 'TurnController@create')->middleware("ACL:adminms-table-turn-create");
        Route::delete('turns/{turn_id}', 'TurnController@delete')->middleware("ACL:adminms-table-turn-delete");
        Route::put('turns/{turn_id}', 'TurnController@update')->middleware("ACL:adminms-table-turn-update");
        //Route::get('turns/{turn_id}/availability', 'TurnController@tableAvailability');
        // Route::get('turns/{turn_id}/unlink-zones/{zone_id}', 'TurnController@unlinkZone', [ 'middleware' =>'ACL:adminms-table-turn-unlinkZone' ]);


        //-----------------------------------------------------
        // MICROSITE::CALENDAR
        //-----------------------------------------------------
        Route::get('calendar/{date}', 'CalendarController@index')->middleware("ACL:adminms-table-calendar-show");
        Route::get('calendar/{date}/zones', 'CalendarController@getZones')->middleware("ACL:adminms-table-calendar-getZones");
        Route::get('calendar/{date}/shifts', 'CalendarController@listShift')->middleware("ACL:adminms-table-calendar-show"); //se esta usando en aplicacion de grid
        Route::post('calendar', 'CalendarController@storeCalendar')->middleware("ACL:adminms-table-calendar-store");
        Route::delete('calendar/{res_turn_id}', 'CalendarController@deleteCalendar')->middleware("ACL:adminms-table-calendar-delete");
        Route::put('calendar/change', 'CalendarController@changeCalendar')->middleware("ACL:adminms-table-calendar-update");
        Route::get('calendar/{turn_id}/{start_time}/{end_time}', 'CalendarController@existConflictTurn')->middleware("ACL:existConflictTurn");

        //-----------------------------------------------------
        // MICROSITE::TABLES
        //-----------------------------------------------------
        // Route::get('tables/availability', 'TableController@availability');
        // Route::get('tables/searchAvailability', 'TableController@searchAvailability');

        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES
        //-----------------------------------------------------
        Route::group(["middleware" =>'ACL:adminms-table-guest-show' ], function() {
            Route::get('guests', 'GuestController@index');
            Route::get('guests/tags', 'GuestTagCategoryController@index');
            Route::get('guests/{guest_id}', 'GuestController@show');
            Route::get('guests/{guest_id}/reservations', 'GuestController@reservation');
        });
        Route::post('guests', 'GuestController@create')->middleware("ACL:adminms-table-guest-store");
        Route::put('guests/{guest_id}', 'GuestController@update')->middleware("ACL:adminms-table-guest-update");


        //-----------------------------------------------------
        // MICROSITE:: HUESPEDES TAGS
        //-----------------------------------------------------
        Route::get('guest-tags/', 'GuestController@listGuestTag')->middleware("ACL:adminms-table-guestTag-show");
        Route::post('guest-tags/', 'GuestController@createGuestTag')->middleware("ACL:adminms-table-guestTag-store");
        Route::delete('guest-tags/{guest_tag_id}', 'GuestController@deleteGuestTag')->middleware("ACL:adminms-table-guestTag-delete");

        //-----------------------------------------------------
        // MICROSITE:: RESERVATION TAGS
        //-----------------------------------------------------
        // Route::resource("reservation/tag", "ReservationTagController", ["only" => ["index", "store", "destroy"]]);
        Route::get('reservation/tag', 'ReservationTagController@index')->middleware("ACL:adminms-table-reservationTag-show");
        Route::post('reservation/tag', 'ReservationTagController@store')->middleware("ACL:adminms-table-reservationTag-store");
        Route::delete('reservation/tag/{tag}', 'ReservationTagController@destroy')->middleware("ACL:adminms-table-reservationTag-delete");

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TURNS
        //-----------------------------------------------------
        // Route::get('zones/{zone_id}/turns', 'ZoneTurnController@index', [ 'middleware' =>'ACL:adminms-table-zoneTurn-show' ]);
        // Route::get('zones/{zone_id}/turns/{id}', 'ZoneTurnController@show', [ 'middleware' =>'ACL:adminms-table-zoneTurn-show' ]);
        // Route::post('zones/{zone_id}/turns', 'ZoneTurnController@create', [ 'middleware' =>'ACL:adminms-table-zoneTurn-store' ]);
        // Route::put('zones/{zone_id}/turns/{id}', 'ZoneTurnController@update', [ 'middleware' =>'ACL:adminms-table-zoneTurn-update' ]);
        // Route::delete('zones/{zone_id}/turns/{id}', 'ZoneTurnController@delete', [ 'middleware' =>'ACL:adminms-table-zoneTurn-delete' ]);

        //-----------------------------------------------------
        // MICROSITE::ZONAS::TYPETURNS::DAYS
        //-----------------------------------------------------
        // Route::get('zones/{zone_id}/type-turns/{id}/days', 'ZoneTypeturnController@index', [ 'middleware' =>'ACL:adminms-table-zoneTypeTurn-index' ]);
        // Route::get('zones/{zone_id}/type-turns/{id}/days/available', 'ZoneTypeturnController@available', [ 'middleware' =>'ACL:adminms-table-zoneTypeTurn-available' ]);

        //-----------------------------------------------------
        // MICROSITE::RESERVATION
        //-----------------------------------------------------
        Route::group(["middleware" =>'ACL:adminms-table-reservation-show' ], function() {
            Route::get('reservations', 'ReservationController@index');
            Route::get('reservations/{reservation_id}', 'ReservationController@show');
        });
        Route::group(["middleware" =>'ACL:adminms-table-reservation-update' ], function() {
            Route::put('reservations/{reservation_id}', 'ReservationController@update');
            Route::patch('reservations/{reservation_id}', 'ReservationController@patch');
            Route::post('reservations/{reservation_id}/grid', 'ReservationController@updateGrid');
        });
        Route::post('reservations', 'ReservationController@create')->middleware("ACL:adminms-table-reservation-store");
        Route::delete('reservations/{reservation_id}', 'ReservationController@delete')->middleware("ACL:adminms-table-reservation-delete");
        Route::post('reservations/{reservation_id}/send-email', 'ReservationController@sendEmail')->middleware("ACL:adminms-table-reservation-email");


        //-----------------------------------------------------
        // MICROSITE:: RESERVATION
        //-----------------------------------------------------

        // Route::resource('table/reservation', 'TableReservationController', ["only" => ["store", "edit", "update"]]);
        Route::group(["middleware" =>'ACL:adminms-table-reservation-store' ], function() {
            Route::post('table/reservation', 'TableReservationController@store');
            Route::post('table/reservation/quickcreate', 'TableReservationController@quickCreate');
        });
        Route::group(["middleware" =>'ACL:adminms-table-reservation-update' ], function() {
            Route::get('table/reservation/{reservation}/edit', 'TableReservationController@edit');
            Route::put('table/reservation/{reservation}', 'TableReservationController@update');
            Route::put('table/reservation/{reservation}/cancel', 'TableReservationController@cancel');
            Route::put('table/reservation/{reservation}/quickedit', 'TableReservationController@quickEdit');
            Route::put('table/reservation/{reservation}/sit', 'TableReservationController@sit');
            Route::put('table/reservation/{reservation}/guest-list', 'TableReservationController@updateGuestList');
        });
        Route::post('waitlist', 'TableReservationController@createWaitList')->middleware("adminms-table-waitlist-store");
        Route::put('waitlist', 'TableReservationController@updateWaitList')->middleware("adminms-table-waitlist-update");
        Route::delete('waitlist/{id}', 'TableReservationController@deleteWaitList')->middleware("adminms-table-waitlist-delete");

        //-----------------------------------------------------
        // MICROSITE:: CONFIGURATION (table res_configuration)
        //-----------------------------------------------------
        Route::patch("configuration/reservations", "ConfigurationController@edit")->middleware("ACL:adminms-table-configurationRes-update");
        // Route::resource("configuration/reservations", "ConfigurationController", ["only" => ["index", "update"]]);
        Route::get("configuration/reservations", "ConfigurationController@index")->middleware("ACL:adminms-table-configurationRes-show");
        Route::put("configuration/reservations/{reservation}", "ConfigurationController@update")->middleware("ACL:adminms-table-configurationRes-update");
        Route::get("configuration/percentages", "PercentageController@index")->middleware("ACL:adminms-table-configurationRes-show");

        // Route::post("configuration/reservations/forms", "ConfigurationController@addFormConfiguration", [ 'middleware' =>'ACL:adminms-table-configurationForm-store' ]);
        // Route::delete("configuration/reservations/forms", "ConfigurationController@removeFormConfiguration", [ 'middleware' =>'ACL:adminms-table-configurationForm-delete' ]);
        // Route::get("configuration/reservations/forms", "ConfigurationController@getForm", [ 'middleware' =>'ACL:adminms-table-configurationForm-show' ]);

        //-----------------------------------------------------
        // MICROSITE:: CODES (table res_code)
        //-----------------------------------------------------
        // Route::resource("configuration/codes", "ConfigurationCodeController", ["only" => ["index", "store", "update", "destroy"]]);
        Route::get("configuration/codes", "ConfigurationCodeController@index")->middleware("ACL:adminms-table-configurationCodes-show");
        Route::post("configuration/codes", "ConfigurationCodeController@store")->middleware("ACL:adminms-table-configurationCodes-store");
        Route::put("configuration/codes/{code}", "ConfigurationCodeController@update")->middleware("ACL:adminms-table-configurationCodes-update");
        Route::delete("configuration/codes/{code}", "ConfigurationCodeController@destroy")->middleware("ACL:adminms-table-configurationCodes-delete");

        //-----------------------------------------------------
        // MICROSITE:: USER (table bs_user)
        //-----------------------------------------------------
        Route::get("configuration/users/privileges", "ConfigurationUserController@getAllUser")->middleware("ACL:adminms-table-configurationUser-show");
        // Route::resource("configuration/users", "ConfigurationUserController", ["only" => ["index", "destroy", "store"]]);
        Route::get("configuration/users", "ConfigurationUserController@index")->middleware("ACL:adminms-table-configurationUser-show");
        Route::delete("configuration/users/{user_id}", "ConfigurationUserController@destroy")->middleware("ACL:adminms-table-configurationUser-delete");
        Route::post("configuration/users", "ConfigurationUserController@store")->middleware("ACL:adminms-table-configurationUser-store");

        //-----------------------------------------------------
        // MICROSITE:: FORM (table res_form)
        //-----------------------------------------------------
        Route::get("configuration/form", "ConfigurationFormController@index")->middleware('ACL:adminms-table-configurationForm-show');
        Route::put("configuration/form", "ConfigurationFormController@update")->middleware( 'ACL:adminms-table-configurationForm-update');

        //-----------------------------------------------------
        // MICROSITE:: Floor
        //-----------------------------------------------------
        Route::group(['prefix' => 'web-app/', 'middleware' => 'ACL:adminms-table-reservation-show'], function () {
            Route::get('floor', 'WebAppController@floor');
            Route::get('grid', 'WebAppController@grid');
            Route::get('book', 'WebAppController@book');
            Route::get('book/history', 'WebAppController@bookHistory');
            Route::get('book/history/reservations', 'WebAppController@bookHistoryReservations');
            Route::get('reservation', 'WebAppController@editReservation');
            Route::get('reservation/{reservation_id}', 'WebAppController@editReservation');
            Route::get('block', 'WebAppController@editBlock');
            Route::get('block/{block_id}', 'WebAppController@editBlock');
        });

        Route::get("notification", "NotificationController@index")->middleware("ACL:adminms-table-notification-show");
        Route::put("notification", "NotificationController@update")->middleware("ACL:adminms-table-notification-update");
    });


    Route::group(['prefix' => 'microsites/{microsite_id}', 'middleware' => ['setLocale', 'setTimeZone']], function () {
        //-----------------------------------------------------
        // MICROSITE:: Reservation Temporal
        //-----------------------------------------------------
        Route::group(['middleware' => ['auth.api']], function () {
            Route::group(['prefix' => 'reservationtemporal'], function () {
                Route::resource("/", "ReservationTemporalController", ["only" => ["index", "destroy", "store"]]);
                Route::get("/expire", "ReservationTemporalController@expire");
                Route::get("/{token}", "ReservationTemporalController@show");
                Route::delete("/{token}", "ReservationTemporalController@destroy");
            });

            //-----------------------------------------------------
            // MICROSITE:: Availability
            //-----------------------------------------------------
            // Route::get('availability/hours', 'AvailabilityController@getHours');
            Route::group(['prefix' => 'availability/'], function () {
                Route::get('basic', 'AvailabilityController@basic');
                // Route::get('zones', 'AvailabilityController@getZones');
                // Route::get('events', 'AvailabilityController@getEvents');
                // Route::get('days', 'AvailabilityController@getDays');
                Route::get('daysdisabled', 'AvailabilityController@getDaysDisabled');
                // Route::get('people', 'AvailabilityController@getPeople');
                Route::get('formatAvailability', 'AvailabilityController@getFormatAvailability');
            });

            Route::post('table/reservation/w', 'TableReservationController@storeFromWeb'); // generar una reservacion desde el widget
            Route::get('table/reservation/confirmed/{crypt}', 'TableReservationController@showByCrypt'); // dovlver una reservacion al widget por un id encriptado
            Route::post('table/reservation/cancel/{crypt}', 'TableReservationController@cancelReserveWeb'); // canelcar una reservacion desde el widget por id encriptado
       });
    });

}

