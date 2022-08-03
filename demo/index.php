<?php

use mon\worker\App;
use mon\worker\interfaces\Middleware;
use mon\worker\support\Container;
use mon\worker\support\Log;
use mon\worker\Request;
use mon\worker\Response;
use mon\worker\Route;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http;
use Workerman\Worker;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);

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

$logConfig = [
    // 日志文件大小
    'maxSize'   => 20480000,
    // 日志目录
    'logPath'   => __DIR__,
    // 日志滚动卷数   
    'rollNum'   => 3,
    // 日志名称，空则使用当前日期作为名称       
    'logName'   => '',
    // 日志分割符
    'splitLine' => '====================================================================================',
    // 是否自动执行save方法保存日志
    'save'      => false,
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
// Route::instance()->get('/', [A::class, 'test']);
Route::instance()->get('/test[/{id:\d+}]', 'A@demo');
Route::instance()->get('/demo', function () {
    // return new Response(200, [], 'demo');
    return 123;
});
Route::instance()->get(['path' => '/', 'befor' => [B::class, C::class]], [A::class, 'test']);


Route::instance()->group(['middleware' => D::class], function ($route) {
    Route::instance()->get(['path' => '/xxx', 'middleware' => [B::class, C::class]], [A::class, 'xxx']);
});

if ($config['listen']) {
    $worker = new Worker($config['listen'], (array)$config['context']);
    $property_map = ['name', 'count', 'user', 'group', 'reusePort', 'transport', 'protocol'];
    foreach ($property_map as $property) {
        if (isset($config[$property])) {
            $worker->$property = $config[$property];
        }
    }




    // 监听事件
    $worker->onWorkerStart = function ($worker) use ($logConfig) {
        // require_once base_path() . '/support/bootstrap.php';

        // 绑定错误处理
        set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });
        register_shutdown_function(function ($start_time) {
            if (time() - $start_time <= 1) {
                sleep(1);
            }
        }, time());

        $container = Container::instance();
        $logger = Log::instance()->setConfig($logConfig);
        $app = App::instance()->init($worker, $container, $logger);
        Http::requestClass(Request::class);
        $worker->onMessage = [$app, 'onMessage'];
    };
}


class A
{
    protected $a = 1;

    public function test(Request $req)
    {
        // debug($id);
        // debug($req);
        // return $res->withBody('test!!!');
        return $req->path();
    }

    public function demo(Request $request, $id = 456)
    {
        return $id;
    }

    public function xxx(Request $request)
    {

        // throw new Exception(123987);
        $this->a++;
        return $this->a;
        // var_dump($request->test);
        // return __METHOD__;
    }
}

class B implements Middleware
{
    public function process(Request $request, callable $callback): Response
    {
        var_dump(__CLASS__);
        // return new Response(200, [], '1123');
        return $callback($request);
    }
}

class C implements Middleware
{
    public function process(Request $request, callable $callback): Response
    {
        var_dump(__CLASS__);
        return $callback($request);
    }
}


class D implements Middleware
{
    public function process(Request $request, callable $callback): Response
    {
        $request->test = 123;
        var_dump(__CLASS__);
        return $callback($request);
    }
}


Worker::runAll();
