<?php

declare(strict_types=1);

namespace mon\worker\interfaces;

use mon\worker\Request;
use mon\worker\Response;

/**
 * 中间件接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Middleware
{
    /**
     * 中间件实现接口
     *
     * @param Request $request      请求实例
     * @param callable $callback    执行下一个中间件回调方法
     * @return Response
     */
    public function process(Request $request, callable $callback): Response;
}
