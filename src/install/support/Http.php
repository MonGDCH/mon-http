<?php

declare(strict_types=1);

namespace support\http;

use ErrorException;
use mon\log\Logger;
use mon\env\Config;
use mon\thinkORM\ORM;
use Workerman\Worker;
use gaia\ProcessTrait;
use mon\http\WorkerMan;
use mon\http\Middleware;
use mon\log\format\LineFormat;
use mon\log\record\FileRecord;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
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
        $app->supportError(Config::instance()->get('http.app.exception', HttpErrorHandler::class));

        // 注册session
        $app->supportSession(Config::instance()->get('http.session', []));

        // 静态文件支持
        $app->supportStaticFile(
            Config::instance()->get('http.app.workerman.static.enable', false),
            Config::instance()->get('http.app.workerman.static.path', ''),
            Config::instance()->get('http.app.workerman.static.ext_type', [])
        );

        // 中间件支持
        Middleware::instance()->load(Config::instance()->get('http.middleware', []));

        // 注册日志处理
        static::registerLogger();

        // 注册路由
        static::registerRoute();

        // 定义数据库配置，自动识别是否已安装Orm库
        if (class_exists(ORM::class)) {
            $config = Config::instance()->get('database', []);
            // 识别是否存在缓存库
            if (class_exists(CacheService::class)) {
                ORM::register(false, $config, Logger::instance()->channel(), CacheService::instance()->getService()->store());
            } else {
                ORM::register(false, $config, Logger::instance()->channel());
            }
            // 注册ORM中间件
            Middleware::instance()->set('__worker__', [ORMMiddleware::class]);
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
        // 路由目录路径
        $routePath = Config::instance()->get('http.app.workerman.route.path', ROOT_PATH . DIRECTORY_SEPARATOR . 'routes');
        if (!is_dir($routePath)) {
            throw new ErrorException('routes dir not found! path: ' . $routePath);
        }

        // 是否递归路由目录
        $recursive = Config::instance()->get('http.app.workerman.route.recursive', false);
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
    public static function registerLogger(string $logChannel = 'http')
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
