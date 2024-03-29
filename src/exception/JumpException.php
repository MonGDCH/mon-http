<?php

declare(strict_types=1);

namespace mon\http\exception;

use Exception;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\BusinessInterface;

/**
 * 路由跳转
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class JumpException extends Exception implements BusinessInterface
{
    /**
     * 响应类实例
     *
     * @var Response
     */
    protected $response;

    /**
     * 构造方法
     *
     * @param Response $response 响应类
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response
    {
        return $this->response;
    }
}
