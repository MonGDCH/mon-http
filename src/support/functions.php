<?php

/*
|--------------------------------------------------------------------------
| 工具类函数支持
|--------------------------------------------------------------------------
| 工具类函数定义文件
|
*/

use mon\http\exception\DumperException;

if (!function_exists('dump')) {
    /**
     * 浏览器打印调试变量
     *
     * @param mixed ...$args    打印的值
     * @throws DumperException
     * @return void
     */
    function dump(...$args)
    {
        throw new DumperException($args);
    }
}
