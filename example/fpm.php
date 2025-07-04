<?php

use mon\http\Fpm;
use mon\http\Response;
use mon\http\Request;
use mon\http\Session;
use mon\http\interfaces\RequestInterface;
use mon\http\Logger as HttpLogger;
use mon\log\Logger;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);

define('RUNTIME_PATH', __DIR__);



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

// $e = new E;
// $t = is_object($e);
// dd($t);
// exit;


$app = new Fpm();
// $app->supportError(E::class);
// $app->supportSession([
//     'session_name'  => 'mysid'
// ]);
// require __DIR__ . '/router.php';

// Logger::instance()->channel()->info('test');
HttpLogger::service()->debug('aaa');


$app->route()->get('/', function (Request $request, Response $response) {
    // $file = __DIR__ . '/router.php';
    // return $response->download($file);
    // return $response->file($file);
    // throw new \Exception('exption');
    Logger::instance()->channel()->info('test22');
    return 'Hello Fpm';
});

$app->route()->post('/test', function (Request $request, Response $response) {
    // $file = __DIR__ . '/router.php';
    // return $response->download($file);
    // return $response->file($file);
    // return 'Hello test!';
    $data = $request->xml();
    dd($data);
});

// 文件路由  http://127.0.0.1:8080/aa/favicon.ico
$app->route()->file('/aa', __DIR__);

$app->run();
