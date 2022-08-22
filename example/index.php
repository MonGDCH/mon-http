<?php

use mon\http\App;
use mon\http\Jump;
use mon\http\Route;
use Workerman\Worker;
use mon\http\Request;
use mon\http\Session;
use mon\http\Response;
use mon\util\Container;
use Workerman\Protocols\Http;
use mon\http\support\ErrorHandler;
use mon\http\interfaces\Middleware;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Session\FileSessionHandler;

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

if ($config['listen']) {
    $worker = new Worker($config['listen'], (array)$config['context']);
    $property_map = ['name', 'count', 'user', 'group', 'reusePort', 'transport', 'protocol'];
    foreach ($property_map as $property) {
        if (isset($config[$property])) {
            $worker->$property = $config[$property];
        }
    }

    // 监听事件
    $worker->onWorkerStart = function ($worker) {
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

        $errorHandler = Container::instance()->get(ErrorHandler::class);
        // 初始化HTTP服务器
        $app = App::instance()->init($worker, $errorHandler, true);
        // 应用扩展支持
        // $app->suppertCallback(true, TTD::class);
        // 静态文件支持
        $app->supportStaticFile(true, __DIR__, ['ico']);
        // session扩展支持
        $app->supportSession(FileSessionHandler::class, ['save_path' => __DIR__ . '/sess/']);
        // 绑定响应请求
        $worker->onMessage = [$app, 'onMessage'];

        // $worker->onClose = function () {
        //     var_dump(1);
        // };
    };
}


class TTD extends Request
{
    public function __construct($buffer)
    {
        parent::__construct($buffer);
    }
}



// 注册路由
// Route::instance()->get('/', [A::class, 'test']);
Route::instance()->get('/test[/{id:\d+}]', 'A@demo');
Route::instance()->get('/demo', function (Request $request) {
    return $request->build('gdmon.com', ['v' => 123]);
});
Route::instance()->get(['path' => '/', 'middleware' => [B::class, C::class]], [A::class, 'test']);
// Route::instance()->get('/s', [(new A), 'test']);


Route::instance()->group(['middleware' => D::class], function ($route) {
    Route::instance()->get(['path' => '/xxx', 'middleware' => [B::class, C::class]], [A::class, 'xxx']);
});

Route::instance()->get('/file', function (Request $request) {
    $response = new Response();
    $file = __DIR__ . '/test.zip';
    // return $response->download($file, 'test.zip');
    return $response->file($file);
});


class A
{
    protected $a = 0;

    public function test(Request $req)
    {
        // $session = $req->session();
        // debug($session);
        // $session->set('bb', '1234');

        // debug(Session::instance()->handler());
        // Session::instance()->set('aab', '1112');
        // Session::instance()->clear();

        // Session::instance()->set('a.b', '1->2');

        return 123;
    }

    public function demo(Request $request, $id = 456)
    {
        return $id;
    }

    public function xxx(Request $request)
    {

        // throw new Exception(123987);
        // $this->a++;
        // $this->a = $this->a + 1;
        // return new Response(200, [], $this->a);
        $this->a = 123;
        return $this->a;
        // var_dump($request->test);
        // return __METHOD__;
    }
}

class B implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // var_dump(__CLASS__);
        // return new Response(200, [], '1123');
        return $callback($request);
    }
}

class C implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // var_dump(__CLASS__);
        return $callback($request);
    }
}


class D implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // $request->test = 123;
        // var_dump($request->controller());
        $response = $callback($request);
        // var_dump(__CLASS__);
        return $response;
    }
}

Worker::runAll();
