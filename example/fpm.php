<?php

use mon\http\Fpm;
use mon\http\fpm\Request;
use mon\http\fpm\Session;
use mon\http\support\ErrorHandler;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);


$errorHandler = new ErrorHandler();
$app = new Fpm($errorHandler);
$app->supportSession();
// require __DIR__ . '/router.php';

$app->route()->get('/', function (Request $request) {
    return 'Hello Fpm';
});


$app->run();
