<?php
/*
|--------------------------------------------------------------------------
| 定义应用请求路由
|--------------------------------------------------------------------------
| 通过Route类进行注册
|
*/

use mon\http\Route;

Route::instance()->get('/[{name}]', function ($name = 'Gaia HTTP') {
    return "Hello {$name}!";
});
