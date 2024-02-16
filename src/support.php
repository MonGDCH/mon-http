<?php

/*
|--------------------------------------------------------------------------
| 初始化支持文件
|--------------------------------------------------------------------------
*/

if (!function_exists('dump')) {
    /**
     * 浏览器打印调试变量
     *
     * @param mixed ...$args    调试打印的值
     * @throws \mon\http\exception\DumperException
     * @return void
     */
    function dump(...$args): void
    {
        throw new \mon\http\exception\DumperException($args);
    }
}


// Gaia环境，进行指令注册
if (PHP_SAPI == 'cli' && class_exists(\gaia\App::class)) {
    $path = __DIR__ . '/command';
    $namespance = 'mon\\http\\command';
    \gaia\App::console()->load($path, $namespance);
}
