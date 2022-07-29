<?php

use mon\worker\App;
use mon\worker\libs\Container;
use mon\worker\Request;
use mon\worker\Response;
use mon\worker\Route;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;

require __DIR__ . '/../vendor/autoload.php';


$config = [
    'listen' => 'http://0.0.0.0:8787',
    'transport' => 'tcp',
    'context' => [],
    'name' => 'webman',
    'count' =>  2,
    'user' => '',
    'group' => '',
    'reusePort' => false,
    'event_loop' => '',
    'stop_timeout' => 2,
    'pid_file' => './webman.pid',
    'status_file' => './webman.status',
    'stdout_file' => './stdout.log',
    'log_file' => './workerman.log',
    'max_package_size' => 10 * 1024 * 1024
];


Worker::$onMasterReload = function () {
    if (function_exists('opcache_get_status')) {
        if ($status = opcache_get_status()) {
            if (isset($status['scripts']) && $scripts = $status['scripts']) {
                foreach (array_keys($scripts) as $file) {
                    opcache_invalidate($file, true);
                }
            }
        }
    }
};

Worker::$pidFile = $config['pid_file'];
Worker::$stdoutFile = $config['stdout_file'];
Worker::$logFile = $config['log_file'];
Worker::$eventLoopClass = $config['event_loop'] ?? '';

TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
if (property_exists(Worker::class, 'statusFile')) {
    Worker::$statusFile = $config['status_file'] ?? '';
}
if (property_exists(Worker::class, 'stopTimeout')) {
    Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
}


// 注册路由
Route::instance()->get('/', [A::class, 'test']);
Route::instance()->get('/test', 'A@demo');
Route::instance()->get('/demo', function () {
    var_dump(1);
});

if ($config['listen']) {
    $worker = new Worker($config['listen'], (array)$config['context']);
    $property_map = ['name', 'count', 'user', 'group', 'reusePort', 'transport', 'protocol'];
    foreach ($property_map as $property) {
        if (isset($config[$property])) {
            $worker->$property = $config[$property];
        }
    }

    // 绑定错误处理
    set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    });
    if ($worker) {
        register_shutdown_function(function ($start_time) {
            if (time() - $start_time <= 1) {
                sleep(1);
            }
        }, time());
    }

    // 监听事件
    $worker->onWorkerStart = function ($worker) {
        // require_once base_path() . '/support/bootstrap.php';
        $container = Container::instance();
        $app = App::instance()->init($worker, $container);
        Http::requestClass(Request::class);
        $worker->onMessage = [$app, 'onMessage'];
    };
}


class A
{
    public function test(Request $request)
    {
        return $request->path();
    }

    public function demo(Request $request)
    {
        return new Response(200, [], 'demo');
    }
}

Worker::runAll();
