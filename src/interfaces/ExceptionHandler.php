<?php

declare(strict_types=1);

namespace mon\http\interfaces;

use Throwable;
use mon\http\Request;
use mon\http\Response;

/**
 * 异常处理接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface ExceptionHandler
{
    /**
     * 上报异常信息
     *
     * @param Throwable $e  错误实例
     * @return mixed
     */
    public function report(Throwable $e, Request $request);

    /**
     * 处理错误信息
     *
     * @param Request $request  请求实例
     * @param Throwable $e      错误实例
     * @return Response
     */
    public function render(Throwable $e, Request $request): Response;
}
