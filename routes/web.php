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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v1'], function() use($router) {
    $router->get('/productos', 'ProductoController@index');
    $router->get('/productos/{id_producto}/{anio}/{mes}/{moneda}/{usuario_consulta}', 'ProductoController@indexUrl');
});
