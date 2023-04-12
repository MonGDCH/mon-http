<?php

declare(strict_types=1);

namespace mon\http\exception;

use RuntimeException;

/**
 * 打印渲染异常信息
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class DumperException extends RuntimeException
{
    /**
     * 打印的数据
     *
     * @var array
     */
    protected $data;

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
}
