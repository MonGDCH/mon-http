<?php

declare(strict_types=1);

namespace mon\http\workerman;

use mon\http\interfaces\SessionInterface;
use Workerman\Protocols\Http\Session as HttpSession;

/**
 * Session工具
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session implements SessionInterface
{
    /**
     * 请求实例
     *
     * @var HttpSession
     */
    protected HttpSession $handler;

    /**
     * 构造方法
     *
     * @param HttpSession $handler  workerman session 驱动
     */
    public function __construct(HttpSession $handler)
    {
        $this->handler = $handler;
    }

    /**
     * 获取驱动实例
     *
     * @return HttpSession
     */
    public function handler(HttpSession $handler = null): HttpSession
    {
        if (!is_null($handler)) {
            $this->handler = $handler;
        }

        return $this->handler;
    }

    /**
     * 设置session, 支持.二级设置
     *
     * @param string $key    键名
     * @param mixed  $value  键值
     * @return Session
     */
    public function set(string $key, mixed $value = null): Session
    {
        if (strpos($key, '.')) {
            list($name1, $name2) = explode('.', $key, 2);
            $origin_value = $this->handler()->get($name1, []);
            if (is_array($origin_value)) {
                $origin_value[$name2] = $value;
                $new_value = $origin_value;
            } else {
                $new_value = [$name2 => $value];
            }
            $this->handler()->set($name1, $new_value);
        } else {
            $this->handler()->set($key, $value);
        }

        return $this;
    }

    /**
     * 获取session值，支持.无限级获取值
     *
     * @param string $key       键名
     * @param mixed  $default   默认值
     * @return mixed
     */
    public function get(string $name = '', mixed $default = null): mixed
    {
        $value = $this->handler()->all();
        if (empty($name)) {
            return $value;
        }

        $keys = explode('.', $name);
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                $value = $default;
                break;
            }
        }
        return $value;
    }

    /**
     * 是否存在某个key，支持.无限级判断
     *
     * @param string $name  键名
     * @return boolean
     */
    public function has(string $name): bool
    {
        $keys = explode('.', $name);
        $value = $this->handler()->all();
        foreach ($keys as $val) {
            if (!isset($value[$val])) {
                return false;
            } else {
                $value = $value[$val];
            }
        }

        return true;
    }

    /**
     * 删除某个key
     *
     * @param string $key 键名
     * @return void
     */
    public function delete(string $name): void
    {
        $this->handler()->delete($name);
    }

    /**
     * 清空session
     *
     * @return void
     */
    public function clear(): void
    {
        $this->handler()->flush();
    }
}
