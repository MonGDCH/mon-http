<?php

declare(strict_types=1);

namespace process;

use gaia\Process;
use mon\log\Logger;
use mon\env\Config;
use Workerman\Worker;
use mon\http\WorkerMan;
use mon\http\Middleware;
use support\http\Bootstrap;

/**
 * HTTP进程服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Http extends Process
{
    /**
     * 启用进程
     *
     * @var boolean
     */
    protected static $enable = true;

    /**
     * 进程配置
     *
     * @var array
     */
    protected static $processConfig = [
        // 监听协议断开
        'listen'    => 'http://0.0.0.0:8080',
        // 通信协议
        'transport' => 'tcp',
        // 额外参数
        'context'   => [],
        // 进程数
        'count'     =>  2,
        // 进程用户
        'user'      => '',
        // 进程用户组
        'group'     => '',
        // 是否开启端口复用
        'reusePort' => false,
    ];

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
        Bootstrap::registerRoute($app, $app->route());

        // 绑定响应请求
        $worker->onMessage = [$app, 'run'];
    }
}
