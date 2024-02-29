<?php

declare(strict_types=1);

namespace process;

use mon\env\Config;
use Workerman\Worker;
use gaia\ProcessTrait;
use mon\http\WorkerMan;
use mon\http\Middleware;
use support\http\Bootstrap;
use gaia\interfaces\ProcessInterface;

/**
 * HTTP进程服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Http implements ProcessInterface
{
    use ProcessTrait;

    /**
     * 是否启用进程
     *
     * @return boolean
     */
    public static function enable(): bool
    {
        return Config::instance()->get('http.app.workerman.enable', false);
    }

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
        $app = new WorkerMan($debug, Config::instance()->get('http.app.newCtrl', true));

        // 自定义错误处理支持
        if (Config::instance()->get('http.app.exception', '')) {
            $app->supportError(Config::instance()->get('http.app.exception', ''));
        }

        // 静态文件支持
        $app->supportStaticFile(
            Config::instance()->get('http.app.workerman.static.enable', false),
            Config::instance()->get('http.app.workerman.static.path', ''),
            Config::instance()->get('http.app.workerman.static.ext_type', [])
        );

        // session扩展支持
        $app->supportSession(Config::instance()->get('http.session', []));

        // 中间件支持
        Middleware::instance()->load(Config::instance()->get('http.middleware', []));

        // 自定义启动时
        Bootstrap::start($app);

        // 注册路由
        Bootstrap::registerRoute();

        // 绑定响应请求
        $worker->onMessage = [$app, 'run'];
    }
}
