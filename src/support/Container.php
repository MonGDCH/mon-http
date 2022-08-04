<?php

declare(strict_types=1);

namespace mon\worker\support;

use mon\util\Container as UtilContainer;
use mon\worker\interfaces\Container as InterfacesContainer;

/**
 * 容器服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Container extends UtilContainer implements InterfacesContainer
{
    /**
     * 创建获取对象的实例
     *
     * @param  string  $name  类名称或标识符
     * @param  array   $vars  绑定的参数
     * @param  boolean $new   是否保存实例
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function make($name, $var = [], $new = true)
    {
        return $this->get($name, $var, $new);
    }
}
