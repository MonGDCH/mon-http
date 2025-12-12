<?php

declare(strict_types=1);

namespace mon\http;

use mon\util\Container;
use mon\log\Logger as Log;

/**
 * 内置日志服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Logger
{
    /**
     * 日志服务实例
     *
     * @var object
     */
    protected static $service;

    /**
     * 初始化日志服务
     *
     * @param string $app   应用名称
     * @return void
     */
    public static function initialization(string $app)
    {
        $config = [
            // 解析器
            'format'    => [
                // 类名
                'handler'   => \mon\log\format\LineFormat::class,
                // 配置信息
                'config'    => [
                    // 日志是否包含级别
                    'level'         => true,
                    // 日志是否包含时间
                    'date'          => true,
                    // 时间格式，启用日志时间时有效
                    'date_format'   => 'Y-m-d H:i:s',
                    // 是否启用日志追踪
                    'trace'         => false,
                    // 追踪层级，启用日志追踪时有效
                    'layer'         => 3
                ]
            ],
            // 记录器
            'record'    => [
                // 类名
                'handler'   => \mon\log\record\FileRecord::class,
                // 配置信息
                'config'    => [
                    // 是否自动写入文件
                    'save'      => false,
                    // 写入文件后，清除缓存日志
                    'clear'     => true,
                    // 日志名称，空则使用当前日期作为名称
                    'logName'   => '',
                    // 日志文件大小
                    'maxSize'   => 20480000,
                    // 日志目录
                    'logPath'   => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $app,
                    // 日志滚动卷数   
                    'rollNum'   => 3
                ]
            ]
        ];
        // 定义HTTP日志通道
        Log::instance()->createChannel($app, $config);
        // 设置为默认的日志通道
        Log::instance()->setDefaultChannel($app);
        static::$service = Log::instance()->channel();
    }

    /**
     * 注册服务
     *
     * @param object|string $service
     * @return Logger
     */
    public static function register($service)
    {
        if (is_string($service)) {
            $service = Container::instance()->get($service);
        }

        static::$service = $service;
    }

    /**
     * 获取日志服务
     *
     * @return \Psr\Log\LoggerInterface|object
     */
    public static function service()
    {
        if (is_null(static::$service)) {
            throw new \RuntimeException('HTTP Logger service not register!');
        }

        return static::$service;
    }

    /**
     * 调用保存日志方法
     *
     * @return void
     */
    public static function save()
    {
        if (method_exists(static::service(), 'saveLog')) {
            static::service()->saveLog();
        } elseif (method_exists(static::service(), 'save')) {
            static::service()->save();
        }
    }
}
