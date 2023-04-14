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
    ''              => [],
    // workerman服务http中间件
    '__worker__'    => [],
    // fpm服务http中间件
    '__fpm__'       => [],
    // 静态资源服务中间件
    '__static__'    => []
];
