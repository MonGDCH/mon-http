<?php

declare(strict_types=1);

namespace support\http;

use mon\log\Logger;
use mon\http\Route;
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
     * 启动
     *
     * @param AppInterface $app 驱动实例
     * @return void
     */
    public static function start(AppInterface $app)
    {
        // 定义默认日志通道
        Logger::instance()->setDefaultChanneel('http');
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
        $route->get('/', function () {
            return 'Hello http process!';
        });

        // 建议require一个路由文件进行定义，支持monitor更新
        // require_once APP_PATH . '/http/router.php';
    }
}
