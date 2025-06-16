<?php

declare(strict_types=1);

namespace support\http;

use mon\env\Config;
use mon\http\Logger;
use mon\thinkORM\ORM;
use Workerman\Worker;
use gaia\ProcessTrait;
use mon\http\WorkerMan;
use mon\http\Middleware;
use support\cache\CacheService;
use mon\thinkORM\ORMMiddleware;
use gaia\interfaces\ProcessInterface;

/**
 * Workerman HTTP 进程服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Http implements ProcessInterface
{
    use ProcessTrait;

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return Config::instance()->get('http.app.workerman.config', []);
    }

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 运行模式
        $debug = Config::instance()->get('app.debug', false);

        // 初始化HTTP服务器
        $app = new WorkerMan($debug, Config::instance()->get('http.app.workerman.newCtrl', true));

        // 注册异常处理器
        $app->supportError(Config::instance()->get('http.app.exception', ErrorHandler::class));

        // 注册session
        $app->supportSession(Config::instance()->get('http.session', []));

        // 静态文件支持
        $app->supportStaticFile(
            Config::instance()->get('http.app.workerman.static.enable', false),
            Config::instance()->get('http.app.workerman.static.path', ''),
            Config::instance()->get('http.app.workerman.static.ext_type', [])
        );

        // 中间件支持
        Middleware::load(Config::instance()->get('http.middleware', []));

        // 注册路由
        static::registerRoute();

        // 定义数据库配置，自动识别是否已安装ORM库
        if (class_exists(ORM::class)) {
            $config = Config::instance()->get('database', []);
            // 注册ORM
            $cache_store = class_exists(CacheService::class) ? CacheService::instance()->getService()->store() : null;
            ORM::register(true, $config, Logger::service(), $cache_store);
            // 注册ORM中间件
            Middleware::set('workerman', [ORMMiddleware::class]);
        }

        // 绑定响应请求
        $worker->onMessage = [$app, 'run'];
    }

    /**
     * 注册路由
     *
     * @return void
     */
    public static function registerRoute()
    {
        // 加载默认路由定义文件
        require_once __DIR__ . '/Route.php';
    }
}
