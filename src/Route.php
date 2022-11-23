<?php

declare(strict_types=1);

namespace mon\http;

use Closure;
use mon\util\File;
use mon\util\Container;
use ReflectionFunction;
use InvalidArgumentException;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use FastRoute\DataGenerator\GroupCountBased;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;

/**
 * 路由封装
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Route
{
    /**
     * fast-route路由容器
     *
     * @var RouteCollector
     */
    protected $collector;

    /**
     * fast-route路由调度
     *
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * 路由信息
     *
     * @var array
     */
    protected $data = [];

    /**
     * 路由组前缀
     *
     * @var string
     */
    protected $groupPrefix = '';

    /**
     * 回调命名空间前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 中间件
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * 错误处理路由
     *
     * @var Closure|array|string
     */
    protected $error;

    /**
     * 设置路由数据
     *
     * @param array $data 路由数据
     * @return Route
     */
    public function setData(array $data): Route
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 获取路由数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data ?: $this->collector()->getData();
    }

    /**
     * 获取fast-route路由容器
     *
     * @return RouteCollector
     */
    public function collector(): RouteCollector
    {
        if (is_null($this->collector)) {
            $this->collector = new RouteCollector(new Std, new GroupCountBased);
        }

        return $this->collector;
    }

    /**
     * 获取fast-route路由调度
     *
     * @return Dispatcher
     */
    public function dispatcher(): Dispatcher
    {
        if (is_null($this->dispatcher)) {
            $this->dispatcher = new Dispatcher($this->getData());
        }

        return $this->dispatcher;
    }

    /**
     * 注册GET路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function get($pattern, $callback): Route
    {
        return $this->map(['GET'], $pattern, $callback);
    }

    /**
     * 注册POST路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function post($pattern, $callback): Route
    {
        return $this->map(['POST'], $pattern, $callback);
    }

    /**
     * 注册PUT路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function put($pattern, $callback): Route
    {
        return $this->map(['PUT'], $pattern, $callback);
    }

    /**
     * 注册PATCH路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function patch($pattern, $callback): Route
    {
        return $this->map(['PATCH'], $pattern, $callback);
    }

    /**
     * 注册DELETE路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function delete($pattern, $callback): Route
    {
        return $this->map(['DELETE'], $pattern, $callback);
    }

    /**
     * 注册OPTIONS路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function options($pattern, $callback): Route
    {
        return $this->map(['OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册任意请求方式的路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Route
     */
    public function any($pattern, $callback): Route
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册组别路由
     *
     * @param  mixed  $pattern  路由前缀
     * @param  Closure $callback 路由回调
     * @return void
     */
    public function group($pattern, Closure $callback): void
    {
        $groupPrefix = $this->groupPrefix;
        $prefix = $this->prefix;
        $middleware  = $this->middleware;

        $parse = $this->parsePattern($pattern);
        $this->groupPrefix .= $parse['path'];
        $this->prefix = $parse['namespace'];
        $this->middleware  = $parse['middleware'];

        call_user_func($callback, $this);

        $this->groupPrefix = $groupPrefix;
        $this->prefix = $prefix;
        $this->middleware  = $middleware;
    }

    /**
     * 注册路由方法
     *
     * @param  array $method   请求方式
     * @param  mixed $pattern  请求模式
     * @param  mixed $callback 路由回调
     * @return Route
     */
    public function map(array $method, $pattern, $callback): Route
    {
        $parse = $this->parsePattern($pattern);
        // 获取请求路径
        $path = $this->groupPrefix . $parse['path'];
        // 获取请求回调
        if (is_string($callback)) {
            $callback = (!empty($parse['namespace']) ? $parse['namespace'] : $this->prefix) . $callback;
        }
        // 所有值转大写
        $method = array_map('strtoupper', $method);

        $result = [
            'middleware' => $parse['middleware'],
            'callback'  => $callback,
        ];
        // 注册fast-route路由表
        $this->collector()->addRoute($method, $path, $result);

        return $this;
    }

    /**
     * 文件路由，类似 golang http.FileServer
     *
     * @param string $path  请求根路径
     * @param string $root  文件目录
     * @param array $ext    允许访问文件扩展名
     * @param array $method 允许请求方式
     * @return void
     */
    public function file(string $path, string $root, array $ext = [], array $method = ['GET', 'POST']): void
    {
        // 生成路由路径
        $paths = [$path == '/' ? '' : $path, '{filePath:.+}'];
        $route_path = implode('/', $paths);
        // 创建路由
        $this->map($method, $route_path, function (string $filePath) use ($root, $ext): Response {
            // 验证请求路径安全
            if (strpos($filePath, '..') !== false || strpos($filePath, "\\") !== false || strpos($filePath, "\0") !== false || strpos($filePath, '//') !== false || !$filePath) {
                return new Response(404);
            }
            // 修正请求路径
            if (preg_match('/%[0-9a-f]{2}/i', $filePath)) {
                $filePath = urldecode($filePath);
            }
            // 验证文件扩展名白名单
            if (!empty($ext) && !in_array(pathinfo($filePath, PATHINFO_EXTENSION), $ext)) {
                return new Response(404);
            }
            // 文件路径
            $file = $root . DIRECTORY_SEPARATOR . $filePath;
            if (!is_file($file)) {
                return new Response(404);
            }
            clearstatcache(true, $file);
            return (new Response())->file($file);
        });
    }

    /**
     * 设置错误处理器
     *
     * @param Closure|array|string $callback
     * @return void
     */
    public function error($callback): void
    {
        // 字符串
        if (is_string($callback)) {
            // 分割字符串获取对象和方法
            $callback = explode('@', $callback, 2);
        }
        // 数组
        if (is_array($callback)) {
            if (!isset($callback[0]) || empty($callback[0]) || !isset($callback[1]) || empty($callback[1])) {
                throw new InvalidArgumentException('Register error handler params faild!');
            }

            $ctrl = Container::instance()->make($callback[0]);
            $callback = [$ctrl, $callback[1]];
        }

        $this->error = $callback;
    }

    /**
     * 获取错误处理器
     *
     * @return \callable|null
     */
    public function getErrorHandler()
    {
        return $this->error;
    }

    /**
     * 解析请求模式
     *
     * @param  mixed $pattern 路由参数
     * @return array
     */
    protected function parsePattern($pattern): array
    {
        $result = [
            // 路由路径或者路由前缀
            'path'      => '',
            // 命名空间
            'namespace' => $this->prefix,
            // 中间件
            'middleware' => $this->middleware,
        ];
        if (is_string($pattern)) {
            // 字符串，标示请求路径
            $result['path'] = $pattern;
        } elseif (is_array($pattern)) {
            // 数组，解析配置
            if (isset($pattern['path']) && !empty($pattern['path'])) {
                $result['path'] = $pattern['path'];
            }
            if (isset($pattern['namespace']) && !empty($pattern['namespace'])) {
                $result['namespace'] = $pattern['namespace'];
            }
            if (isset($pattern['middleware']) && !empty($pattern['middleware'])) {
                $result['middleware'] = array_merge($this->middleware, (array) $pattern['middleware']);
            }
        }

        return $result;
    }

    /**
     * 执行路由
     *
     * @param  string $method 请求类型
     * @param  string $path   请求路径
     * @return array
     */
    public function dispatch(string $method, string $path): array
    {
        return $this->dispatcher()->dispatch($method, $path);
    }

    /**
     * 获取路由缓存结果集,或者缓存路由
     *
     * @param  string $path 缓存文件路径，存在缓存路径则输出缓存文件
     * @return mixed
     */
    public function cache(string $path = '')
    {
        $data = $this->getData();
        array_walk_recursive($data, [$this, 'buildClosure']);
        $content = var_export($data, true);
        $content = str_replace(['\'[__start__', '__end__]\''], '', stripcslashes($content));
        // 不存在缓存文件路径，返回缓存结果集
        if (empty($path)) {
            return $content;
        }
        // 缓存路由文件
        $cache = '<?php ' . PHP_EOL . 'return ' . $content . ';';
        return File::instance()->createFile($cache, $path, false);
    }

    /**
     * 生成路由内容
     *
     * @param  mixed  &$value 路由内容
     * @return void
     */
    protected function buildClosure(&$value)
    {
        if ($value instanceof Closure) {
            $reflection = new ReflectionFunction($value);
            $startLine  = $reflection->getStartLine();
            $endLine    = $reflection->getEndLine();
            $file       = $reflection->getFileName();
            $item       = file($file);
            $content    = '';
            for ($i = $startLine - 1, $j = $endLine - 1; $i <= $j; $i++) {
                $content .= $item[$i];
            }
            $start = strpos($content, 'function');
            $end   = strrpos($content, '}');
            $value = '[__start__' . substr($content, $start, $end - $start + 1) . '__end__]';
        }
    }
}
