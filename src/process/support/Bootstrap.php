<?php

declare(strict_types=1);

namespace support\http;

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
        // 初始化的一些程序
    }

    /**
     * 注册路由
     *
     * @param AppInterface $app 驱动实例
     * @param Route $route  路由器
     * @return void
     */
    public static function registerRoute(AppInterface $app, Route $route)
    {
        // 注册路由
        $route->get('/', function () {
            return 'Hello http process!';
        });

        // 建议require一个路由文件进行定义，支持monitor更新
        // require_once APP_PATH . '/http/router.php';
    }
}
