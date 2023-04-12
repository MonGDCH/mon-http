<?php

declare(strict_types=1);

namespace mon\http\interfaces;

use mon\http\Route;

/**
 * App服务接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface AppInterface
{
    /**
     * 获取运行模式
     *
     * @return boolean
     */
    public function debug(): bool;

    /**
     * 获取请求实例
     *
     * @return RequestInterface
     */
    public function request(): RequestInterface;

    /**
     * 获取错误处理服务实例
     *
     * @return ExceptionHandlerInterface
     */
    public function exceptionHandler(): ExceptionHandlerInterface;

    /**
     * 自定义请求类支持
     *
     * @param string $request_class 请求类名
     * @return AppInterface
     */
    public function supportRequest(string $request_class): AppInterface;

    /**
     * Session支持
     *
     * @param array $config  Session配置
     * @return AppInterface
     */
    public function supportSession(array $config = []): AppInterface;

    /**
     * 自定义错误处理类支持
     *
     * @param string $error_class 错误处理类名
     * @return AppInterface
     */
    public function supportError(string $error_class): AppInterface;

    /**
     * 获取路由实例
     *
     * @return Route
     */
    public function route(): Route;
}
