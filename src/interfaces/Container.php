<?php

declare(strict_types=1);

namespace mon\http\interfaces;

use Psr\Container\ContainerInterface;

/**
 * 容器服务接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Container extends ContainerInterface
{
    /**
     * 创建获取对象的实例
     *
     * @param  string  $name    类名称或标识符
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function make($name);
}
