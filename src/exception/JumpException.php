<?php

declare(strict_types=1);

namespace mon\worker\exception;

use Exception;
use mon\worker\Response;

/**
 * 路由跳转
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class JumpException extends Exception
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
     * 获取响应实例
     *
     * @return Response
     */
    final public function getResponse()
    {
        return $this->response;
    }
}
