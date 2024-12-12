<?php

declare(strict_types=1);

namespace mon\http\support;

use mon\log\Logger;
use mon\http\Route;
use mon\util\Instance;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use mon\http\exception\RouteException;

/**
 * 工具类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Utils
{
    use Instance;

    /**
     * 加载路由
     *
     * @param string $routePath     路由目录路径
     * @param boolean $recursive    是否递归路由目录
     * @return void
     */
    public function loadRoute(string $routePath, bool $recursive = false): void
    {
        if (!is_dir($routePath)) {
            throw new RouteException('Routes dir not found! path: ' . $routePath);
        }
        // 获取指定目录内容
        $iterator = new RecursiveDirectoryIterator($routePath, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        // 是否递归目录
        $iterator = $recursive ? new RecursiveIteratorIterator($iterator) : $iterator;
        // 加载路由文件，映射路由$route变量
        Route::instance()->group('', function (Route $route) use ($iterator) {
            /** @var RecursiveDirectoryIterator $iterator */
            foreach ($iterator as $file) {
                // 过滤目录及非文件
                if ($file->isDir() || $file->getExtension() != 'php') {
                    continue;
                }
                // 加载文件
                require_once $file->getPathname();
            }
        });
    }

    /**
     * 注册日志通道
     *
     * @param string $channel   通道名
     * @param boolean $default  是否设置为默认通道
     * @param array $config     配置信息
     * @return void
     */
    public static function registerLogger(string $channel, bool $default = true, array $config = [])
    {
        // 默认日志配置，保存在文件中
        $defaulConfig = [
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
                    'logPath'   => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $channel,
                    // 日志滚动卷数   
                    'rollNum'   => 3
                ]
            ]
        ];
        $config = empty($config) ? $defaulConfig : $config;

        // 定义HTTP日志通道
        Logger::instance()->createChannel($channel, $config);
        // 设置为默认的日志通道
        $default && Logger::instance()->setDefaultChannel($channel);
    }
}
