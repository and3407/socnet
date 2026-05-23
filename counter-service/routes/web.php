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
    // Get total unread count for authenticated user
    $router->get('/counters/total', 'CounterController@total');
    // Get unread counts for all dialogs
    $router->get('/counters/dialogs', 'CounterController@dialogs');
    // Get unread count for specific dialog
    $router->get('/counters/dialogs/{dialogId}', 'CounterController@dialog');
    // Get total unread count for user by ID (public endpoint)
    $router->get('/counters/user/{userId}', 'CounterController@user');
});
