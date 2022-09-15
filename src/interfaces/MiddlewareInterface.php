<?php

declare(strict_types=1);

namespace mon\http\interfaces;

use Closure;
use mon\http\Request;
use mon\http\Response;

/**
 * 中间件接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface MiddlewareInterface
{
    /**
     * 中间件实现接口
     *
     * @param Request $request  请求实例
     * @param Closure $callback 执行下一个中间件回调方法
     * @return Response
     */
    public function process(Request $request, Closure $callback): Response;
}
