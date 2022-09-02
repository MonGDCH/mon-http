<?php



// 定义路由

use mon\http\interfaces\Middleware;
use mon\http\Request;
use mon\http\Response;
use mon\http\Route;

$app->route()->get('/', function (Request $request) {
    return 'Hello World!';
});
// 中间件、控制器定义
$app->route()->get(['path' => '/midd', 'middleware' => [MyMiddleware::class]], [MyController::class, 'index']);
// 定义组别路由
$app->route()->group(['path' => '/group', 'middleware' => [MyMiddleware::class, MyMiddlewareTwo::class]], function (Route $route) {
    // 基于fast-route，支持路由参数输入
    $route->get('/test[/{id:\d+}]', function ($request, $id = 1) {
        return $id;
    });
    // 字符串方式定义控制器
    $route->get('/ctrl', 'MyController@json');
});
// 定义错误路由
$app->route()->any('*', function ($request) {
    return 'error';
});


// 定义中间件
class MyMiddleware implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // 执行前置逻辑...

        return $callback($request);
    }
}
class MyMiddlewareTwo implements Middleware
{
    public function process(Request $request, Closure $callback): Response
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
    public function index(Request $request)
    {
        // 返回response对象
        $response = new Response(200, [], 'response');
        return $response;
    }

    public function json(Request $request)
    {
        // 返回数组，自动转Json
        return ['code'  => 200];
    }

    public function text(Request $request)
    {
        // 直接返回字符串
        return 'HTML';
    }
}
