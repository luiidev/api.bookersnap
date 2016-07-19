<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return $app->version();
});

$app->get('/docs', function () use ($app) {
    return "documentacion del API";
});

$app->group(['prefix' => 'v1/{lang}','namespace' => 'App\Http\Controllers'], function($app)
{
	// Zonas 
    $app->get('zones','ZoneController@index');
    $app->get('zone/{id}','ZoneController@getZone');
    $app->post('zone','ZoneController@createZone');
    $app->put('zone/{id}','ZoneController@updateZone');
    $app->delete('zone/{id}','ZoneController@deleteZone');

});

$app->group(['prefix' => 'v1/{lang}/zone/{id}','namespace' => 'App\Http\Controllers'], function($app)
{
    // Mesas
    $app->get('tables','TableController@index');
    $app->get('table/{tid}','TableController@getTable');
    $app->post('table','TableController@createTable');
    $app->put('table/{tid}','TableController@updateTable');
    $app->delete('table/{tid}','TableController@deleteTable');

});