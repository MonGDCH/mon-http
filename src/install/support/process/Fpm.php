<?php

declare(strict_types=1);

namespace support\http\process;

use mon\env\Config;
use mon\http\Route;
use mon\http\Logger;
use mon\http\Router;
use mon\thinkORM\ORM;
use mon\http\Middleware;
use mon\http\Fpm as Http;
use support\cache\CacheService;
use mon\thinkORM\ORMMiddleware;
use mon\http\support\ErrorHandler;

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
            if ($debug) {
                echo 'Gaia框架FPM服务未启用，请修改配置文件启动FPM服务';
            } else {
                $disabled = $app->getFallback();
                $app->send($disabled());
            }
            exit;
        }

        // 注册异常处理器
        $app->supportError(Config::instance()->get('http.app.exception', ErrorHandler::class));

        // 注册session
        $app->supportSession(Config::instance()->get('http.session', []));

        // 注册中间件
        Middleware::load(Config::instance()->get('http.middleware', []));

        // 注册路由
        static::registerRoute();

        // 定义数据库配置，自动识别是否已安装ORM库
        if (class_exists(ORM::class)) {
            $config = Config::instance()->get('database', []);
            // 注册ORM
            $cache_store = class_exists(CacheService::class) ? CacheService::instance()->getService()->store() : null;
            ORM::register(false, $config, Logger::service(), $cache_store);
            // 注册ORM中间件
            Middleware::set('fpm', [ORMMiddleware::class]);
        }

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
        // 加载路由缓存文件
        $cache_route_file = Config::instance()->get('http.app.fpm.cache', '');
        if ($cache_route_file && file_exists($cache_route_file)) {
            $data = require $cache_route_file;
            Router::registerRoutes($data['routers']);
            Route::instance()->setData($data['routerData']);
            return;
        }

        // 加载默认路由定义文件
        require_once dirname(__DIR__) . '/Route.php';
    }
}
