<?php

declare(strict_types=1);

namespace mon\http;

use Fiber;
use WeakMap;
use stdClass;
use SplObjectStorage;
use Workerman\Events\Swow;
use Workerman\Events\Revolt;
use Workerman\Events\Swoole;

/**
 * 请求上下文管理，请求结束自动销毁
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Context
{
    /**
     * 存储
     *
     * @var mixed
     */
    protected static $storage;

    /**
     * 数据对象
     *
     * @var stdClass
     */
    protected static $obj;

    /**
     * 获取存储对象
     *
     * @return stdClass
     */
    protected static function getObj(): stdClass
    {
        if (!static::$storage) {
            static::$storage = class_exists(WeakMap::class) ? new WeakMap() : new SplObjectStorage();
            static::$obj = new stdClass();
        }
        $key = static::getKey();
        if (!isset(static::$storage[$key])) {
            static::$storage[$key] = new stdClass();
        }

        return static::$storage[$key];
    }

    /**
     * 获取存储索引
     *
     * @return mixed
     */
    protected static function getKey()
    {
        // switch (Worker::$eventLoopClass) {
        //     case Revolt::class:
        //         return Fiber::getCurrent();
        //     case Swoole::class:
        //         return \Swoole\Coroutine::getContext();
        //     case Swow::class:
        //         return \Swow\Coroutine::getCurrent();
        // }
        return static::$obj;
    }

    /**
     * 获取存储数据
     *
     * @param string $key   键名，空则获取所有
     * @param mixed $default    默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $obj = static::getObj();
        if ($key === '') {
            return $obj;
        }

        return $obj->$key ?? $default;
    }

    /**
     * 设置存储数据
     *
     * @param string $key   键名
     * @param mixed $value  值
     * @return void
     */
    public static function set(string $key, $value): void
    {
        $obj = static::getObj();
        if ($key !== '') {
            $obj->$key = $value;
        }
    }

    /**
     * 删除存储的数据
     *
     * @param string $key   键名
     * @return void
     */
    public static function delete(string $key): void
    {
        $obj = static::getObj();
        unset($obj->$key);
    }

    /**
     * 是否存在某个存储键
     *
     * @param string $key   键名
     * @return boolean
     */
    public static function has(string $key): bool
    {
        $obj = static::getObj();
        return property_exists($obj, $key);
    }

    /**
     * 清除存储对象
     *
     * @return void
     */
    public static function destroy(): void
    {
        unset(static::$storage[static::getKey()]);
    }
}