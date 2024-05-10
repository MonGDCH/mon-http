<?php

declare(strict_types=1);

namespace support\http;

use ErrorException;
use mon\env\Config;
use mon\log\Logger;
use mon\http\Route;
use mon\http\Middleware;
use mon\http\Fpm as Http;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * HTTP初始化
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Fpm
{
    /**
     * 启动
     *
     * @return void
     */
    public static function run()
    {
        // 运行模式
        $debug = Config::instance()->get('app.debug', false);
        // 初始化HTTP服务器
        $app = new Http($debug);
        // 是否启用FPM应用
        if (!Config::instance()->get('http.app.fpm.enable', false)) {
            $disabled = $app->getFallback();
            $app->send($disabled());
            exit;
        }

        // 注册异常处理器
        $app->supportError(Config::instance()->get('http.app.exception', HttpErrorHandler::class));

        // 注册session
        $app->supportSession(Config::instance()->get('http.session', []));

        // 注册中间件
        Middleware::instance()->load(Config::instance()->get('http.middleware', []));

        // 注册日志处理
        static::registerLogger();

        // 注册路由
        static::registerRoute();

        // 运行FPM
        $app->run();
    }


    /**
     * 注册路由
     *
     * @return void
     */
    public static function registerRoute()
    {
        // 判断路由缓存
        $cache_route_file = Config::instance()->get('http.app.fpm.route.cache', '');
        if ($cache_route_file && file_exists($cache_route_file)) {
            $data = require $cache_route_file;
            Route::instance()->setData($data);
            return;
        }

        // 动态加载路由
        $routePath = Config::instance()->get('http.app.fpm.route.path', ROOT_PATH . DIRECTORY_SEPARATOR . 'routes');
        if (!is_dir($routePath)) {
            throw new ErrorException('routes dir not found! path: ' . $routePath);
        }
        // 是否递归路由目录
        $recursive = Config::instance()->get('http.app.recursive', false);
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
     * @param string $logChannel  日志通道名
     * @return void
     */
    public static function registerLogger($logChannel = 'fpm')
    {
        // 定义HTTP日志通道
        Logger::instance()->createChannel($logChannel, [
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
                    'logPath'   => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $logChannel,
                    // 日志滚动卷数   
                    'rollNum'   => 3
                ]
            ]
        ]);
        // 设置为默认的日志通道
        Logger::instance()->setDefaultChannel($logChannel);
    }
}
