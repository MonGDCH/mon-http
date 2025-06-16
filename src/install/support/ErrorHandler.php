<?php

declare(strict_types=1);

namespace support\http;

use Throwable;
use mon\http\interfaces\RequestInterface;
use mon\util\exception\ValidateException;

/**
 * 异常错误处理
 *
 * @author  Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ErrorHandler extends \mon\http\support\ErrorHandler
{
    /**
     * 不需要记录信息（日志）的异常类列表
     *
     * @var array
     */
    protected $ignoreReport = [
        ValidateException::class
    ];

    /**
     * 上报异常信息
     *
     * @param Throwable $e  错误实例
     * @param RequestInterface $request  请求实例
     * @return mixed
     */
    public function report(Throwable $e, RequestInterface $request)
    {
        // 记录日志
        parent::report($e, $request);
    }
}
