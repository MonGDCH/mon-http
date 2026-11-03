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

// 通用路由：注册命名空间，自动解析 PathInfo 映射到控制器方法
// /admin/sys/auth/role/edit => App\Http\Controller\Admin\sys\auth\RoleController::edit
// /admin/sys/auth/role 基于默认方法 index 兜底 => App\Http\Controller\Admin\sys\auth\RoleController::index
$app->route()->auto('/admin', 'App\Http\Controller\Admin');

// 通用路由配置方式
$app->route()->auto([
    // 路由前缀，为空表示不限制前缀
    'path'       => '/admin',
    // 控制器命名空间
    'namespace'  => 'App\Http\Controller\Admin',
    // PathInfo 分割符，默认 /
    'split'      => '/',
    // 控制器类名后缀，默认 Controller
    'suffix'     => 'Controller',
    // 默认方法名，默认 index
    'action'     => 'index',
    // 允许的请求方式，为空不限制
    'method'     => ['GET', 'POST'],
    // 中间件
    'middleware' => [MiddlewareA::class],
    // 控制器文件目录，未接入 PSR-4 自动加载时按目录加载
    'dir'        => APP_PATH . '/http/controller/admin',
    // 是否允许调用控制器继承的公共方法，默认 false
    'inherit'    => false,
]);

// 通用路由说明
// 1、显式注册的路由优先级高于通用路由，只有未命中显式路由时才会解析通用路由
// 2、默认只允许调用控制器自身声明的 public 非静态方法，拒绝下划线/魔术方法
// 3、开启路由缓存后通用路由配置会一并缓存，无需再执行路由文件


#### 路由缓存

使用 `cache()` 生成路由缓存源码，需要在路由闭包中遵守以下约定：

1. 闭包内引用的类名必须使用完全限定名（如 `\mon\http\Response`），缓存后不会在路由文件中重新执行 `use` 语句
2. 不支持箭头函数（`fn() => ...`）、绑定了 `$this` 的闭包以及 `use` 绑定对象变量，缓存时会抛出 `RouteException`
3. 请求路径会先执行 `rawurldecode` 再进入调度，保证 `%20` 等编码路径能够正确匹配

```php
<?php

// 错误处理
$app->route()->error([App\Controller\Index::class, 'index']);


// 路由命名
Router::get('/test', [App\Controller\Index::class,'test'])->name('test');

```
