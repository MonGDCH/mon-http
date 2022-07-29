<?php

namespace mon\worker\interfaces;

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
     * 反射执行回调方法
     *
     * @param  mixed  $callback 回调方法
     * @param  array  $vars     参数
     * @return mixed
     */
    public function invoke($callback, $vars = []);

    /**
     * 反射执行对象实例化，支持构造方法依赖注入
     *
     * @param  string $class 对象名称
     * @param  array  $vars  绑定构造方法参数
     * @return mixed
     */
    public function invokeClass($class, $vars = []);

    /**
     * 执行类方法， 绑定参数
     *
     * @param  string|array $method 类方法, 用@分割, 如: Test@say | [Test::class, 'say']
     * @param  array        $vars   绑定参数
     * @return mixed
     */
    public function invokeMethd($method, $vars = []);

    /**
     * 绑定参数，执行函数或者闭包
     *
     * @param  mixed $function 函数或者闭包
     * @param  array $vars     绑定参数
     * @return mixed
     */
    public function invokeFunction($function, $vars = []);
}
