<?php

declare(strict_types=1);

namespace support\http;

use mon\log\Logger;
use mon\http\Route;
use mon\orm\gaia\ORM;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
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
        // 数据库链接
        ORM::register(true);
    }

    /**
     * 注册路由
     *
     * @param Route $route  路由器
     * @return void
     */
    public static function registerRoute(Route $route)
    {
        // 存在路由缓存则直接加载路由缓存，在FPM的环境下有优化性能的作用
        if (defined('IN_FPM') && file_exists(ROUTE_CACHE_PATH)) {
            $data = require ROUTE_CACHE_PATH;
            $route->setData($data);
            return;
        }

        // 注册路由
        // $route->get('/', function () {
        //     return 'Hello http process!';
        // });

        // 建议require一个路由文件进行定义，支持monitor更新
        require_once APP_PATH . '/http/router.php';
    }

    /**
     * 注册日志处理
     *
     * @return void
     */
    protected static function registerLogger()
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
