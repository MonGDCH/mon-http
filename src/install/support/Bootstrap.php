<?php

declare(strict_types=1);

namespace support\http;

use ErrorException;
use mon\env\Config;
use mon\log\Logger;
use mon\http\Route;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use mon\http\interfaces\AppInterface;

/**
 * HTTP初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Bootstrap
{
    /**
     * 日志通道
     *
     * @var string
     */
    protected static $logChannel = 'http';

    /**
     * 启动
     *
     * @param AppInterface $app 驱动实例
     * @return void
     */
    public static function start(AppInterface $app)
    {
        // 日志处理
        static::registerLogger();
    }

    /**
     * 注册路由
     *
     * @return void
     */
    public static function registerRoute()
    {
        // 路由目录路径
        $routePath = Config::instance()->get('http.app.routePath', ROOT_PATH . DIRECTORY_SEPARATOR . 'routes');
        // 是否递归路由目录
        $recursive = Config::instance()->get('http.app.recursive', false);

        if (!is_dir($routePath)) {
            throw new ErrorException('routes dir not found! path: ' . $routePath);
        }

        // 获取路由实例，供require使用
        $route = Route::instance();
        // 获取指定目录内容
        $iterator = new RecursiveDirectoryIterator($routePath, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        // 是否递归目录
        $iterator = $recursive ? new RecursiveIteratorIterator($iterator) : $iterator;
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $file) {
            // 过滤目录及非文件
            if ($file->isDir() || $file->getExtension() != 'php') {
                continue;
            }
            // 加载文件
            require_once $file->getPathname();
        }
    }

    /**
     * 注册日志处理
     *
     * @return void
     */
    public static function registerLogger()
    {
        // 定义HTTP日志通道
        Logger::instance()->createChannel(static::$logChannel, [
            // 解析器
            'format'    => [
                // 类名
                'handler'   => LineFormat::class,
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
                'handler'   => FileRecord::class,
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
                    'logPath'   => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . static::$logChannel,
                    // 日志滚动卷数   
                    'rollNum'   => 3
                ]
            ]
        ]);
        // 设置为默认的日志通道
        Logger::instance()->setDefaultChannel(static::$logChannel);
    }
}
