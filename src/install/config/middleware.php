<?php

/*
|--------------------------------------------------------------------------
| 中间件配置文件
|--------------------------------------------------------------------------
| 定义全局中间件
|
*/

return [
    // 全局中间件
    ''              => [
        \support\http\middleware\LoggerMiddleware::class,
        \support\http\middleware\FirewallMiddleware::class,
        \support\http\middleware\ThrottleMiddleware::class
    ],
    // workerman服务http中间件
    '__worker__'    => [],
    // fpm服务http中间件
    '__fpm__'       => [],
    // 静态资源服务中间件
    '__static__'    => []
];
