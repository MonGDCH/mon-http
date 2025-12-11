<?php

declare(strict_types=1);

namespace mon\http\interfaces;

/**
 * Session服务接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface SessionInterface
{
    /**
     * 设置session, 支持.二级设置
     *
     * @param string $key   键名
     * @param mixed $value  键值
     * @return SessionInterface
     */
    public function set(string $key, $value = null): SessionInterface;

    /**
     * 获取session值，支持.无限级获取值
     *
     * @param string $key       键名
     * @param mixed  $default   默认值
     * @return mixed
     */
    public function get(string $name = '', $default = null);

    /**
     * 是否存在某个key，支持.无限级判断
     *
     * @param string $name  键名
     * @return boolean
     */
    public function has(string $name): bool;

    /**
     * 删除session
     *
     * @param  string $key 键名
     * @return void
     */
    public function delete(string $key);

    /**
     * 清空Session
     *
     * @return void
     */
    public function clear();
}
