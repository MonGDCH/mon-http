<?php

declare(strict_types=1);

namespace process;

use mon\log\Logger;
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
        return Config::instance()->get('http.process.enable', false);
    }

    /**
     * 获取进程配置
     *
     * @return array
     */
    public static function getProcessConfig(): array
    {
        return Config::instance()->get('http.process.config', []);
    }

    /**
     * 进程启动
     *
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        // 定义默认日志通道
        Logger::instance()->setDefaultChanneel('default');
        // 运行模式
        $debug = Config::instance()->get('app.debug', true);
        // 获取配置
        $httpConfig = Config::instance()->get('http');
        $appConfig = $httpConfig['app'];
        // 初始化HTTP服务器
        $app = new WorkerMan($debug, $appConfig['newCtrl']);
        // 自定义错误处理支持
        if (isset($appConfig['exception']) && !empty($appConfig['exception'])) {
            $app->supportError($appConfig['exception']);
        }
        // 静态文件支持
        $staticConfig = $httpConfig['static'];
        $app->supportStaticFile($staticConfig['enable'], $staticConfig['path'], $staticConfig['ext_type']);

        // session扩展支持
        $app->supportSession($httpConfig['session']);

        // 中间件支持
        $middlewareConfig = $httpConfig['middleware'];
        Middleware::instance()->load($middlewareConfig);

        // 自定义启动时
        Bootstrap::start($app);

        // 注册路由
        Bootstrap::registerRoute($app->route());

        // 绑定响应请求
        $worker->onMessage = [$app, 'run'];
    }
}