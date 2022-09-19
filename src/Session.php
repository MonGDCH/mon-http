<?php

declare(strict_types=1);

namespace mon\http;

use mon\util\Instance;
use Workerman\Protocols\Http\Session as SessionBase;

/**
 * Session工具
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session
{
    use Instance;

    /**
     * 请求实例
     *
     * @var Request
     */
    protected $request = null;

    /**
     * 私有化构造方法
     */
    protected function __construct()
    {
    }

    /**
     * 绑定Request请求实例
     *
     * @param Request $request
     * @return Session
     */
    public function request(Request $request = null): Session
    {
        $this->request = $request;
        return $this;
    }

    /**
     * 获取驱动实例
     *
     * @return SessionBase
     */
    public function handler(): SessionBase
    {
        return $this->request->session();
    }

    /**
     * 设置session, 支持.二级设置
     *
     * @param string $key    键名
     * @param mixed  $value  键值
     * @return Session
     */
    public function set(string $key, $value = null): Session
    {
        if (strpos($key, '.')) {
            list($name1, $name2) = explode('.', $key);
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
    public function get(string $name = '', $default = null)
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
     * 是否存在某个key
     *
     * @param string $name  键名
     * @return boolean
     */
    public function has(string $name): bool
    {
        return $this->handler()->has($name);
    }

    /**
     * 删除某个key
     *
     * @param string $name
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
