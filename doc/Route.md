# 路由定义

结合`nikic/fast-route`库实现路由功能

#### 使用说明

```php
<?php

// 控制器调用演示
$app->route()->group(['path' => '/controller','middleware' => MiddlewareC::class], function($r){
    $r->get('', [Controller::class, 'action']);
});

// 控制器调用演示
$app->route()->group(['path' => '/class', 'namespace' => '\App\Controller\\'], function($r){
    $r->get('', 'Index@action');
});

// 匿名方法调用
$app->route()->post(['path' => '/test', 'middleware' => [MiddlewareA::class, MiddlewareB::class]], function($request){
    return 'This is Middleware and after demo!';
});

// 多种请求方式
$app->route()->map(['GET', 'POST'], '/', function(Request $request){
	return ['code' => 1, 'dataType' => 'json'];
});

// 文件下载
$app->route()->get('/download', function(Request $request){
    $file = 'test.txt';
    $response = new Response();
    $response->download($file);
    return $response;
});


// 默认路由, 没有对应路径的时候，调用 * 回调
$app->route()->any('*', 'App\Controller\Index@index');



```

