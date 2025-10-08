<?php

declare(strict_types=1);

namespace mon\http\exception;

use RuntimeException;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\BusinessInterface;

/**
 * 打印渲染异常信息
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class DumperException extends RuntimeException implements BusinessInterface
{
    /**
     * 打印的数据
     *
     * @var array
     */
    protected array $data = [];

    /**
     * 构造方法
     *
     * @param mixed $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * 获取打印的数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response
    {
        $tmp = [];
        foreach ($this->getData() as $val) {
            if (defined('IN_WORKERMAN') && IN_WORKERMAN) {
                $tmp[] = '<pre>' . dd($val, false) . '<pre/><br/>';
            } else {
                $tmp[] =  dd($val, false) . '<br/>';
            }
        }

        return new Response(200, [], implode('', $tmp));
    }
}
