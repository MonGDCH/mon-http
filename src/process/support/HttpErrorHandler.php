<?php

declare(strict_types=1);

namespace support\http;

use Throwable;
use mon\http\support\ErrorHandler;
use mon\http\interfaces\RequestInterface;

/**
 * 异常错误处理
 *
 * @author  Mon <985558837@qq.com>
 * @version 1.0.0
 */
class HttpErrorHandler extends ErrorHandler
{
    /**
     * 上报异常信息
     *
     * @param Throwable $e  错误实例
     * @param RequestInterface $request  请求实例
     * @return mixed
     */
    public function report(Throwable $e, RequestInterface $request)
    {
        // TODO 记录日志
        if (class_exists(\mon\log\Logger::class)) {
            $log = 'method：' . $request->method() . ' URL：' . $request->path() . ' file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' message: ' . $e->getMessage();
            \mon\log\Logger::instance()->channel()->error($log);
        }
    }
}
