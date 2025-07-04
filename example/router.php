<?php



// 定义路由

use mon\http\Route;
use mon\http\Request;
use mon\http\Response;
use mon\http\Session;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\MiddlewareInterface;
use mon\log\Logger;

/** @var \mon\http\Route $route */
$route = $app->route();

$route->get('/', function (A $aa) {
    Session::instance()->set('test', 123456);
    return 'Hello World!' . $aa->getName();
});

$route->get('/get', function () {
    return Session::instance()->get('test');
});

// 中间件、控制器定义
$route->get(['path' => '/midd', 'middleware' => [MyMiddleware::class]], [MyController::class, 'index']);
// 定义组别路由
$route->group(['path' => '/group', 'middleware' => [MyMiddleware::class, MyMiddlewareTwo::class]], function (Route $route) {
    // 基于fast-route，支持路由参数输入
    $route->get('/test[/{id:\d+}]', function (int $id = 1) {
        return $id;
    });
    // 字符串方式定义控制器
    $route->get('/ctrl', 'MyController@json');
});

$route->get('/text', ['MyController', 'text']);

// 文件路由 http://127.0.0.1:8080/aa/route.php
$route->group(['path' => '/a', 'middleware' => MyMiddleware::class], function ($route) {
    $route->file('/aa', __DIR__);
});

// 定义错误路由
// $route->error(function ($request) {
//     return 'error: ' . $request->path();
// });

$route->post('/test', function (Request $request, Response $response) {
    $data = $request->xml();
    return $response->withBody(json_encode($data));
});


// 定义中间件
class MyMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, Closure $callback): Response
    {
        // 执行前置逻辑...
        dd(1);
        return $callback($request);
    }
}
class MyMiddlewareTwo implements MiddlewareInterface
{
    public function process(RequestInterface $request, Closure $callback): Response
    {
        // 执行前置逻辑...

        $response = $callback($request);

        // 还可以执行后置逻辑...

        return $response;
    }
}

// 定义控制器
class MyController
{
    public function __construct(Request $request, A $a)
    {
        $request->test = '123';
        dd($request->path());
    }

    public function index(Request $request, Response $response)
    {
        // 返回response对象
        // $response = new Response(200, [], 'response');
        return $response->withBody($request->host() . ' send response');
    }

    public function json(A $a, Request $request)
    {
        dd($request->test);
        // 返回数组，自动转Json
        Logger::instance()->channel()->info('123456');
        return ['code'  => 200, 'class' => $a->getName()];
    }

    public function text(Request $request)
    {
        dd($request->getName('test 111'));
        dd($request->aa);
        // 直接返回字符串
        return 'HTML' . ' => ' . $request->controller() . '@' . $request->action();
    }
}

class A
{
    public function getName()
    {
        return __CLASS__;
    }
}
