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

if (!function_exists('response')) {
    /**
     * 获取响应实例
     *
     * @param integer $status   状态码
     * @param array $headers    请求头
     * @param string $body      响应内容
     * @return \mon\http\Response
     */
    function response(int $status = 200, array $headers = [], string $body = ''): \mon\http\Response
    {
        return new \mon\http\Response($status, $headers, $body);
    }
}

if (!function_exists('request')) {
    /**
     * 获取请求实例
     *
     * @return \mon\http\Request
     */
    function request(): \mon\http\Request
    {
        return \mon\util\Context::get(\mon\http\Request::class);
    }
}
