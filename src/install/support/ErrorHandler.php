<?php

declare(strict_types=1);

namespace support\http;

use Throwable;
use mon\http\Response;
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
    protected array $ignoreReport = [
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

    /**
     * 处理错误信息
     *
     * @param Throwable $e      错误实例
     * @param RequestInterface $request  请求实例
     * @param boolean $debug 是否调试模式     
     * @return Response
     */
    public function render(Throwable $e, RequestInterface $request, bool $debug = false): Response
    {
        // 处理参数验证异常响应
        if ($e instanceof ValidateException) {
            return new Response(200, ['Content-Type' => 'application/json;charset=utf-8'], json_encode([
                'code' => $e->getCode(),
                'msg'  => $e->getMessage(),
                'data' => [],
            ], JSON_UNESCAPED_UNICODE));
        }

        return parent::render($e, $request, $debug);
    }
}
