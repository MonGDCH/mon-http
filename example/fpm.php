<?php

use mon\http\Fpm;
use mon\http\Response;
use mon\http\fpm\Request;
use mon\http\fpm\Session;
use mon\http\interfaces\RequestInterface;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);

/**
 * 自定义错误接管
 */
class E extends \mon\http\support\ErrorHandler
{
    public function render(Throwable $e, RequestInterface $request, bool $debug = false): Response
    {
        return new Response(500, [], 'test');
    }
}

$app = new Fpm();
// $app->supportError(E::class);
// $app->supportSession();
// require __DIR__ . '/router.php';

$app->route()->get('/', function (Request $request, Response $response) {
    // $file = __DIR__ . '/router.php';
    // return $response->download($file);
    // return $response->file($file);
    return 'Hello Fpm';
});


$app->run();
