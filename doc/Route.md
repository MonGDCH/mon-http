# 路由定义

结合`nikic/fast-route`库实现路由功能

#### 使用说明

```php

<?php

// 控制器调用演示
$app->route()->group(['path' => '/controller','middleware' => MiddlewareC::class], function($route){
    $route->get('', [Controller::class, 'action']);
});

// 控制器调用演示
$app->route()->group(['path' => '/class', 'namespace' => '\App\Controller\\'], function($route){
    $route->get('', 'Index@action');
});

// 匿名方法调用
$app->route()->post(['path' => '/test', 'middleware' => [MiddlewareA::class, MiddlewareB::class]], function($request){
    return 'This is Middleware and after demo! ' . $request->host();
});

// 多种请求方式
$app->route()->map(['GET', 'POST'], '/', function(Request $request){
	return ['code' => 1, 'dataType' => 'json'];
});

// 文件下载
$app->route()->get('/download', function(Request $request, Response $response){
    $file = 'test.txt';
    $response->download($file);
    return $response;
});

// 文件路由
$app->route()->file('/static', '/home/public/static');


// 错误处理
$app->route()->error([App\Controller\Index::class, 'index']);



```
