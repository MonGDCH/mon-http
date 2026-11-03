<?php

declare(strict_types=1);

namespace mon\http;

use Closure;
use mon\util\File;
use mon\util\Container;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use InvalidArgumentException;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use mon\http\exception\RouteException;
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
     * 通用路由配置在路由数据中的存储键名，用于路由缓存兼容
     *
     * @var string
     */
    const AUTO_ROUTE_KEY = 'autoRoute';

    /**
     * 单例实体
     *
     * @var Route
     */
    protected static $instance = null;

    /**
     * fast-route路由容器
     *
     * @var RouteCollector
     */
    protected $collector = null;

    /**
     * fast-route路由调度
     *
     * @var Dispatcher
     */
    protected $dispatcher = null;

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
     * 通用路由配置
     *
     * @var array
     */
    protected $autoRoutes = [];

    /**
     * 单例化路由实例
     */
    protected function __construct() {}

    /**
     * 获取单例
     *
     * @return Route
     */
    public static function instance(): Route
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * 加载路由
     *
     * @param string $routePath     路由目录路径
     * @param boolean $recursive    是否递归路由目录
     * @return void
     */
    public static function load(string $routePath, bool $recursive = false)
    {
        if (!is_dir($routePath)) {
            throw new RouteException('Routes dir not found! path: ' . $routePath);
        }
        // 获取指定目录内容
        $iterator = new RecursiveDirectoryIterator($routePath, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        // 是否递归目录
        $iterator = $recursive ? new RecursiveIteratorIterator($iterator) : $iterator;
        // 加载路由文件，映射路由$route变量
        static::instance()->group('', function (Route $route) use ($iterator) {
            /** @var RecursiveDirectoryIterator $iterator */
            foreach ($iterator as $file) {
                // 过滤目录及非文件
                if ($file->isDir() || $file->getExtension() != 'php') {
                    continue;
                }
                // 加载文件
                require_once $file->getPathname();
            }
        });
    }

    /**
     * 设置路由数据
     *
     * @param array $data 路由数据
     * @return Route
     */
    public function setData(array $data): Route
    {
        // 兼容路由缓存中的通用路由配置
        if (isset($data[self::AUTO_ROUTE_KEY])) {
            $this->autoRoutes = (array) $data[self::AUTO_ROUTE_KEY];
            unset($data[self::AUTO_ROUTE_KEY]);
        }
        $this->data = $data;
        // 路由数据更新后重置调度实例，避免继续使用旧的调度器
        $this->dispatcher = null;
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
     * @return Router
     */
    public function get($pattern, $callback): Router
    {
        return $this->map(['GET'], $pattern, $callback);
    }

    /**
     * 注册POST路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function post($pattern, $callback): Router
    {
        return $this->map(['POST'], $pattern, $callback);
    }

    /**
     * 注册PUT路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function put($pattern, $callback): Router
    {
        return $this->map(['PUT'], $pattern, $callback);
    }

    /**
     * 注册PATCH路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function patch($pattern, $callback): Router
    {
        return $this->map(['PATCH'], $pattern, $callback);
    }

    /**
     * 注册DELETE路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function delete($pattern, $callback): Router
    {
        return $this->map(['DELETE'], $pattern, $callback);
    }

    /**
     * 注册OPTIONS路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function options($pattern, $callback): Router
    {
        return $this->map(['OPTIONS'], $pattern, $callback);
    }

    /**
     * 注册任意请求方式的路由
     *
     * @param  mixed  $pattern  请求模式
     * @param  mixed  $callback 路由回调
     * @return Router
     */
    public function any($pattern, $callback): Router
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
    public function group($pattern, Closure $callback)
    {
        $groupPrefix = $this->groupPrefix;
        $prefix = $this->prefix;
        $middleware  = $this->middleware;

        $parse = $this->parsePattern($pattern);
        $this->groupPrefix = $this->normalizePath($this->groupPrefix . $parse['path']);
        $this->prefix = $parse['namespace'];
        $this->middleware  = $parse['middleware'];

        // 使用 try/finally 保证路由组状态在异常时也能正确还原
        try {
            call_user_func($callback, $this);
        } finally {
            $this->groupPrefix = $groupPrefix;
            $this->prefix = $prefix;
            $this->middleware  = $middleware;
        }
    }

    /**
     * 注册路由方法
     *
     * @param  array $method   请求方式
     * @param  mixed $pattern  请求模式
     * @param  mixed $callback 路由回调
     * @return Router
     */
    public function map(array $method, $pattern, $callback): Router
    {
        $parse = $this->parsePattern($pattern);
        // 获取请求路径，统一规范化，避免出现多余斜杠
        $path = $this->normalizePath($this->groupPrefix . $parse['path']);
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
        // 路由表更新后重置调度实例，保证新注册的路由可以立即被调度
        $this->dispatcher = null;

        return new Router($path, $method);
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
    public function file(string $path, string $root, array $ext = [], array $method = ['GET', 'POST'])
    {
        // 生成路由路径
        $paths = [$path == '/' ? '' : $path, '{filePath:.+}'];
        $route_path = implode('/', $paths);
        // 创建路由
        $this->map($method, $route_path, function (string $filePath) use ($root, $ext): Response {
            // 统一由 resolveFile 完成解码、安全校验和根目录包含验证
            $file = \mon\http\Route::resolveFile($root, $filePath, $ext);
            if (is_null($file)) {
                return new Response(404);
            }

            clearstatcache(true, $file);
            return (new Response())->file($file);
        });
    }

    /**
     * 解析文件路由的安全文件路径
     *
     * 必须先解码再做安全校验，最后通过 realpath 验证文件位于根目录内，
     * 避免类似 %2e%2e%2f 的编码路径穿越。
     *
     * @param string $root      文件根目录
     * @param string $filePath  请求文件路径
     * @param array $ext        允许访问的扩展名白名单，空则不限制
     * @return string|null      安全的真实文件路径，失败返回 null
     */
    public static function resolveFile(string $root, string $filePath, array $ext = array()): ?string
    {
        // 根目录为空时 realpath 会返回当前工作目录，必须直接拒绝
        if (trim($root) === '') {
            return null;
        }
        // 解码后再校验，防止编码绕过安全检查
        $path = rawurldecode(trim((string) $filePath));
        if ($path === '' || strpos($path, "\0") !== false || strpos($path, "\\") !== false) {
            return null;
        }
        // 逐段校验，拒绝空段、当前目录、上级目录以及仍然包含编码痕迹的段
        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || preg_match('/[%\x00-\x1f]/', $segment)) {
                return null;
            }
        }
        // 验证文件扩展名白名单
        if (!empty($ext) && !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), array_map('strtolower', $ext), true)) {
            return null;
        }
        // 验证根目录真实性
        $rootPath = realpath($root);
        if ($rootPath === false || !is_dir($rootPath)) {
            return null;
        }
        // 验证文件真实路径位于根目录内（同时拦截符号链接逃逸）
        $file = realpath($rootPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments));
        if ($file === false || !is_file($file)) {
            return null;
        }
        $prefix = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($file, $prefix) !== 0) {
            return null;
        }

        return $file;
    }

    /**
     * 注册通用路由
     *
     * 注册一个控制器命名空间（或控制器目录），自动解析当前请求的 PathInfo 并映射到对应的控制器方法：
     * PathInfo 按 $split 分割，末段为方法名、倒数第二段为控制器类名（自动补 $suffix），其余段作为子命名空间；
     * 当末段无法匹配到方法时，回退为「全部段作为控制器路径 + 默认方法 $action」。
     *
     * @example
     * // /admin/sys/auth/role/edit => app\http\controller\admin\sys\auth\RoleController::edit
     * $route->auto('/admin', 'app\http\controller\admin');
     * // 带配置的注册方式
     * $route->auto([
     *     'path'      => '/admin',
     *     'namespace' => 'app\http\controller\admin',
     *     'split'     => '/',
     *     'suffix'    => 'Controller',
     *     'action'    => 'index',
     *     'method'    => ['GET', 'POST'],
     *     'middleware' => [LoginMiddleware::class],
     *     'dir'       => APP_PATH . '/http/controller/admin',
     * ]);
     *
     * @param string|array $pattern    路由前缀，或通用路由配置数组
     * @param string|null $namespace   控制器命名空间
     * @return void
     */
    public function auto($pattern, ?string $namespace = null)
    {
        $config = is_array($pattern) ? $pattern : ['path' => (string) $pattern];
        $parse = $this->parsePattern($config);
        // 控制器命名空间，支持在路由组内注册时继承当前命名空间前缀
        $ns = is_null($namespace) ? (string) ($config['namespace'] ?? '') : $namespace;
        $namespace = rtrim($this->normalizeNamespace($this->prefix . ltrim($ns, '\\')), '\\');
        // 通用路由前缀，'' 表示不限制前缀
        $path = $this->normalizePath($this->groupPrefix . $parse['path']);

        $this->autoRoutes[] = [
            // 通用路由前缀
            'path'      => $path === '/' ? '' : $path,
            // 控制器命名空间
            'namespace' => $namespace,
            // PathInfo 分割符
            'split'     => (string) ($config['split'] ?? '/'),
            // 控制器类名后缀
            'suffix'    => (string) ($config['suffix'] ?? 'Controller'),
            // 默认方法名
            'action'    => (string) ($config['action'] ?? 'index'),
            // 允许的请求方式，空数组表示不限制
            'method'    => array_map('strtoupper', (array) ($config['method'] ?? [])),
            // 中间件
            'middleware' => $parse['middleware'],
            // 控制器文件目录，用于未接入 PSR-4 自动加载的场景
            'dir'       => (string) ($config['dir'] ?? ''),
            // 是否允许调用控制器继承的公共方法
            'inherit'   => (bool) ($config['inherit'] ?? false),
        ];
    }

    /**
     * 获取通用路由配置
     *
     * @return array
     */
    public function getAutoRoutes(): array
    {
        return $this->autoRoutes;
    }

    /**
     * 设置错误处理器
     *
     * @param Closure|array|string $callback
     * @return void
     */
    public function error($callback)
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
     * @return mixed
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
                // 命名空间支持嵌套，拼接当前命名空间前缀
                $result['namespace'] = $this->normalizeNamespace($this->prefix . ltrim((string) $pattern['namespace'], '\\'));
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
     * 显式注册的路由优先，未命中时才尝试通用路由
     *
     * @param  string $method 请求类型
     * @param  string $path   请求路径
     * @return array
     */
    public function dispatch(string $method, string $path): array
    {
        $dispatch = $this->dispatcher()->dispatch($method, $path);
        // 命中显式路由
        if ($dispatch[0] === Dispatcher::FOUND) {
            return $dispatch;
        }
        // 未命中路由时，尝试解析通用路由
        if ($dispatch[0] === Dispatcher::NOT_FOUND && !empty($this->autoRoutes)) {
            $handler = $this->resolveAutoRoute($method, $path);
            if (!is_null($handler)) {
                return [Dispatcher::FOUND, $handler, []];
            }
        }

        return $dispatch;
    }

    /**
     * 解析通用路由
     *
     * @param string $method 请求类型
     * @param string $path   请求路径
     * @return array|null 路由回调信息，未匹配返回 null
     */
    protected function resolveAutoRoute(string $method, string $path): ?array
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        foreach ($this->autoRoutes as $config) {
            $prefix = (string) ($config['path'] ?? '');
            $split = (string) ($config['split'] ?? '/');
            $split = $split === '' ? '/' : $split;
            // 路由前缀匹配，并校验前缀边界，防止 /admin 匹配到 /administrator
            if ($prefix !== '') {
                if (strpos($path, $prefix) !== 0) {
                    continue;
                }
                $rest = substr($path, strlen($prefix));
                if ($rest !== '' && $rest[0] !== '/' && $rest[0] !== $split) {
                    continue;
                }
            } else {
                $rest = $path;
            }
            $rest = ltrim($rest, '/');
            if ($split !== '/') {
                $rest = ltrim($rest, $split);
            }
            $rest = rtrim($rest, '/');
            if ($split !== '/') {
                $rest = rtrim($rest, $split);
            }
            if ($rest === '') {
                continue;
            }
            // 请求方式白名单
            if (!empty($config['method']) && !in_array($method, $config['method'], true)) {
                continue;
            }

            $handler = $this->parseAutoPath($rest, $config);
            if (!is_null($handler)) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * 解析通用路由的 PathInfo 为路由回调信息
     *
     * @param string $path   去除前缀后的请求路径
     * @param array $config  通用路由配置
     * @return array|null
     */
    protected function parseAutoPath(string $path, array $config): ?array
    {
        $split = (string) ($config['split'] ?? '/');
        $split = $split === '' ? '/' : $split;
        $segments = array_values(array_filter(explode($split, $path), function ($segment) {
            return $segment !== '';
        }));
        if (empty($segments)) {
            return null;
        }
        // 路径段只允许字母、数字、下划线、连字符，杜绝路径穿越与非法字符注入
        foreach ($segments as $segment) {
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $segment)) {
                return null;
            }
        }

        $action = (string) ($config['action'] ?? 'index');
        // 候选规则：1、末段为方法名；2、全部段为控制器路径，使用默认方法名
        $candidates = [];
        if (count($segments) > 1) {
            $candidates[] = [array_slice($segments, 0, -1), $segments[count($segments) - 1]];
        }
        $candidates[] = [$segments, $action];

        foreach ($candidates as $candidate) {
            list($ctrlSegments, $actionName) = $candidate;
            $class = $this->buildAutoClass($ctrlSegments, $config);
            if (is_null($class)) {
                continue;
            }
            $methodName = $this->resolveAutoAction($class, $actionName, !empty($config['inherit']));
            if (is_null($methodName)) {
                continue;
            }

            return [
                'middleware' => (array) ($config['middleware'] ?? []),
                'callback'   => [$class, $methodName],
            ];
        }

        return null;
    }

    /**
     * 构建控制器类名，并在必要时加载控制器文件
     *
     * @param array $segments 控制器路径段，末段为控制器名称
     * @param array $config   通用路由配置
     * @return string|null
     */
    protected function buildAutoClass(array $segments, array $config): ?string
    {
        $namespace = (string) ($config['namespace'] ?? '');
        $suffix = (string) ($config['suffix'] ?? 'Controller');
        $last = (string) array_pop($segments);
        $class = $namespace === '' ? '' : $namespace . '\\';
        foreach ($segments as $segment) {
            $class .= $segment . '\\';
        }
        $class .= $this->studly($last) . $suffix;
        // 未接入 PSR-4 自动加载时，尝试从指定目录加载控制器文件
        if (!class_exists($class) && !empty($config['dir'])) {
            $this->loadAutoClassFile((string) $config['dir'], $segments, $this->studly($last) . $suffix);
        }
        if (!class_exists($class)) {
            return null;
        }
        // 抽象类、接口、trait 无法实例化
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            return null;
        }

        return $reflection->getName();
    }

    /**
     * 加载通用路由的控制器文件
     *
     * @param string $dir       控制器根目录
     * @param array $segments   子目录路径段
     * @param string $name      控制器类名（文件名）
     * @return void
     */
    protected function loadAutoClassFile(string $dir, array $segments, string $name)
    {
        // 目录为空时 realpath 会返回当前工作目录，必须直接拒绝
        if (trim($dir) === '') {
            return;
        }
        $root = realpath($dir);
        if ($root === false || !is_dir($root)) {
            return;
        }
        $file = $root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments) . DIRECTORY_SEPARATOR . $name . '.php';
        $real = realpath($file);
        if ($real === false || !is_file($real)) {
            return;
        }
        // 验证文件位于控制器根目录内
        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($real, $prefix) !== 0) {
            return;
        }

        require_once $real;
    }

    /**
     * 解析通用路由的控制器方法
     *
     * @param string $class    控制器类名
     * @param string $action   请求方法名
     * @param bool $inherit    是否允许调用继承的公共方法
     * @return string|null
     */
    protected function resolveAutoAction(string $class, string $action, bool $inherit = false): ?string
    {
        // 支持 URL 中使用连字符映射到控制器的下划线方法
        $methods = array_unique([$action, str_replace('-', '_', $action)]);
        foreach ($methods as $method) {
            // 拒绝私有约定方法（下划线开头）和魔术方法
            if ($method === '' || strpos($method, '_') === 0 || !method_exists($class, $method)) {
                continue;
            }
            $reflection = new ReflectionMethod($class, $method);
            // 仅允许调用公共、非静态、非抽象、非魔术方法
            if (!$reflection->isPublic() || $reflection->isStatic() || $reflection->isAbstract() || $reflection->isConstructor() || $reflection->isDestructor()) {
                continue;
            }
            // 默认只允许调用控制器自身声明的方法，避免误调用基类的公共方法
            if (!$inherit && $reflection->getDeclaringClass()->getName() !== $class) {
                continue;
            }

            return $reflection->getName();
        }

        return null;
    }

    /**
     * 转换路径段为驼峰类名
     *
     * @param string $value
     * @return string
     */
    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    /**
     * 规范化路由路径
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $path = (string) preg_replace('#//+#', '/', $path);

        return $path;
    }

    /**
     * 规范化命名空间，保证非空时以 \ 结尾
     *
     * @param string $namespace
     * @return string
     */
    protected function normalizeNamespace(string $namespace): string
    {
        return $namespace === '' ? '' : rtrim($namespace, '\\') . '\\';
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
        // 通用路由配置一并缓存，保证使用路由缓存时通用路由依然生效
        if (!empty($this->autoRoutes)) {
            $data[self::AUTO_ROUTE_KEY] = $this->autoRoutes;
        }
        array_walk_recursive($data, [$this, 'buildClosure']);
        $content = var_export($data, true);
        $content = str_replace(['\'[__start__', '__end__]\''], '', stripcslashes($content));
        // 不存在缓存文件路径，返回缓存结果集
        if (empty($path)) {
            return $content;
        }
        // 缓存路由文件
        $cache = '<?php ' . PHP_EOL . 'return ' . $content . ';';
        return File::createFile($cache, $path, false);
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
