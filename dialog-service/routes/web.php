<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

// API v1
$router->group(['prefix' => 'api/v1', 'namespace' => 'Api\V1'], function () use ($router) {
    // Send message
    $router->post('/messages', 'MessageController@send');
    // Get dialog with user
    $router->get('/dialogs/{userId}', 'MessageController@getDialog');
});
