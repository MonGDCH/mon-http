<?php

declare(strict_types=1);

namespace mon\http\exception;

use Exception;

/**
 * 路由异常
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class RouteException extends Exception
{
    /**
     * 异常相关请求头
     *
     * @var array
     */
    protected $header = [];

    /**
     * 设置异常相关
     *
     * @param mixed $data 移除信息
     * @return RouteException
     */
    public function setHeader(array $header): RouteException
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 获取相关数据
     *
     * @return array
     */
    public function getHeader(): array
    {
        return $this->header;
    }
}
