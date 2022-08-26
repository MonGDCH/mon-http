<?php

use mon\http\App;
use mon\http\interfaces\Middleware;
use mon\http\Request;
use mon\http\Response;
use mon\http\Route;
use mon\http\support\ErrorHandler;
use mon\util\Container;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Session\FileSessionHandler;

require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 'on');
error_reporting(E_ALL);

// 绑定错误处理
set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
    if (error_reporting() & $level) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
});
register_shutdown_function(function ($start_time) {
    if (Worker::getAllWorkers() && time() - $start_time <= 1) {
        sleep(1);
    }
}, time());

$config = [
    // workerman监听IP断开
    'listen' => 'http://0.0.0.0:8787',
    // workerman传输层协议
    'transport' => 'tcp',
    // workerman额外参数
    'context' => [],
    // 进程名
    'name' => 'http',
    // 进程数
    'count' =>  2,
    // 运行用户
    'user' => '',
    // 运行组
    'group' => '',
    // workerman端口复用
    'reusePort' => false,
    // workerame事件运行驱动类
    'event_loop' => '',
    // workerman断开连接后强制kill连接时间
    'stop_timeout' => 2,
    // 存储主进程PID的文件
    'pid_file' => './http.pid',
    // 存储主进程状态文件的文件
    'status_file' => './http.status',
    // 存储标准输出的文件
    'stdout_file' => './stdout.log',
    // 存储日志文件
    'log_file' => './workerman.log',
    // workerman最大可接受数据包大小
    'max_package_size' => 10 * 1024 * 1024
];

// 定义workerman全局配置
TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;
Worker::$pidFile = $config['pid_file'];
Worker::$stdoutFile = $config['stdout_file'];
Worker::$logFile = $config['log_file'];
Worker::$eventLoopClass = $config['event_loop'] ?? '';
Worker::$statusFile = $config['status_file'] ?? '';
Worker::$stopTimeout = $config['stop_timeout'] ?? 2;
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

// 开启程序
$worker = new Worker($config['listen'], (array)$config['context']);
$property_map = ['name', 'count', 'user', 'group', 'reusePort', 'transport', 'protocol'];
foreach ($property_map as $property) {
    if (isset($config[$property])) {
        $worker->$property = $config[$property];
    }
}

// 监听事件
$worker->onWorkerStart = function ($worker) {
    // 加载公共的worker配置
    // require_once __DIR__ . '/bootstrap.php';

    $errorHandler = Container::instance()->get(ErrorHandler::class);
    // 初始化HTTP服务器
    $app = App::instance()->init($worker, $errorHandler, true);
    // 应用扩展支持
    $app->suppertCallback(true);
    // 静态文件支持
    $app->supportStaticFile(true, __DIR__, ['ico']);
    // session扩展支持
    $app->supportSession(FileSessionHandler::class, ['save_path' => __DIR__ . '/sess/']);
    // 绑定响应请求
    $worker->onMessage = [$app, 'onMessage'];
};


// 定义路由
Route::instance()->get('/', function ($request) {
    return 'Hello World';
});
// 中间件、控制器定义
Route::instance()->get(['path' => '/midd', 'middleware' => [MyMiddleware::class]], [MyController::class, 'index']);
// 定义组别路由
Route::instance()->group(['path' => '/group', 'middleware' => [MyMiddleware::class, MyMiddlewareTwo::class]], function (Route $route) {
    // 基于fast-route，支持路由参数输入
    $route->get('/test[/{id:\d+}]', function ($request, $id = 1) {
        return $id;
    });
    // 字符串方式定义控制器
    $route->get('/ctrl', 'MyController@json');
});
// 定义错误路由
Route::instance()->any('*', function ($request) {
    return 'error';
});


// 定义中间件
class MyMiddleware implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // 执行前置逻辑...

        return $callback($request);
    }
}
class MyMiddlewareTwo implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // 执行前置逻辑...

        $response = $callback($request);

        // 还可以执行后置逻辑...

        return $response;
    }
}

// 定义控制器
class MyController
{
    public function index(Request $request)
    {
        // 返回response对象
        $response = new Response(200, [], 'response');
        return $response;
    }

    public function json(Request $request)
    {
        // 返回数组，自动转Json
        return ['code'  => 200];
    }

    public function text(Request $request)
    {
        // 直接返回字符串
        return 'HTML';
    }
}



// 运行workerman
Worker::runAll();
