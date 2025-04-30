<?php

use mon\http\Fpm;
use mon\http\Response;
use mon\http\Request;
use mon\http\Session;
use mon\http\interfaces\RequestInterface;
use mon\http\Route;
use mon\http\Router;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);

Route::instance()->group([], function (Route $route) {
    $route->get('/', function (Request $request, Response $response) {
        dd($request->fullUrl());
        return 'Hello Fpm';
    })->name('index');

    $route->get('/test', function (Request $request, Response $response) {
        dd(Router::getRouters());
        return 'test';
    })->name('test');

    $route->get('/demo', function (Request $request, Response $response) {
        return route('test');
    })->name('demo');
});

$app = new Fpm();

$app->run();
