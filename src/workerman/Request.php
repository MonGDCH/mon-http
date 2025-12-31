<?php

declare(strict_types=1);

namespace mon\http\workerman;

use mon\util\Tool;
use RuntimeException;
use InvalidArgumentException;
use mon\http\libs\UploadFile;
use Workerman\Connection\TcpConnection;
use mon\http\libs\Request as LibsRequest;
use mon\http\interfaces\RequestInterface;
use Workerman\Protocols\Http\Request as WorkermanRequest;

/**
 * 请求处理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Request implements RequestInterface
{
    use LibsRequest;

    /**
     * workerman请求对象
     *
     * @var WorkermanRequest
     */
    protected $service;

    /**
     * 构造方法
     *
     * @param string $buffer
     */
    public function __construct($buffer)
    {
        $this->service = new WorkermanRequest($buffer);
    }

    /**
     * 获取 Workerman 请求对象
     *
     * @return WorkermanRequest
     */
    public function service(): WorkermanRequest
    {
        return $this->service;
    }

    /**
     * 获取链接
     *
     * @return TcpConnection
     */
    public function connection(): TcpConnection
    {
        if (!$this->service()->connection) {
            throw new RuntimeException('Request connection not initialization!');
        }
        return $this->service()->connection;
    }

    /**
     * 获取GET数据
     *
     * @param string|null $name 参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get(?string $name = null, $default = null, bool $filter = true)
    {
        $result = $this->service()->get($name, $default);
        return $filter && $result ? $this->filter($result) : $result;
    }

    /**
     * 获取POST数据
     *
     * @param string|null $name 参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function post(?string $name = null, $default = null, bool $filter = true)
    {
        $result = $this->service()->post($name, $default);
        return $filter && $result ? $this->filter($result) : $result;
    }

    /**
     * 获取header信息
     *
     * @param mixed $name    参数键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function header(?string $name = null, $default = null)
    {
        return $this->service()->header($name, $default);
    }

    /**
     * 获取$_SERVER数据
     *
     * @param  mixed $name    参数键名
     * @param  mixed $default 默认值
     * @return mixed
     */
    public function server(?string $name = null, $default = null)
    {
        return is_null($name) ? $_SERVER : $this->getData($_SERVER, $name, $default);
    }

    /**
     * 获取请求Session
     *
     * @param string|null $name    session键名
     * @param mixed $default       默认值
     * @return mixed
     */
    public function session(?string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->service()->session();
        }
        $value = $this->service()->session()->all();
        return is_null($name) || $name === '' ? $value : $this->getData($value, $name, $default);
    }

    /**
     * 获取请求Cookie
     *
     * @param string|null $name cookie名
     * @param mixed $default    默认值
     * @return mixed
     */
    public function cookie(?string $name = null, $default = null)
    {
        return $this->service()->cookie($name, $default);
    }

    /**
     * 获取上传文件
     *
     * @param string|null $name 文件名
     * @return null|UploadFile[]|UploadFile
     */
    public function file(?string $name = null)
    {
        $files = $this->service()->file($name);
        if (null === $files) {
            return $name === null ? [] : null;
        }
        if ($name !== null) {
            // 多文件
            if (is_array(current($files))) {
                return $this->parseFiles($files);
            }
            return $this->parseFile($files);
        }
        $upload_files = [];
        foreach ($files as $name => $file) {
            // 多文件
            if (is_array(current($file))) {
                $upload_files[$name] = $this->parseFiles($file);
            } else {
                $upload_files[$name] = $this->parseFile($file);
            }
        }

        return $upload_files;
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method(): string
    {
        return $this->service()->method();
    }

    /**
     * 获取请求host
     *
     * @return string
     */
    public function host($without_port = false): string
    {
        return $this->service()->host($without_port);
    }

    /**
     * 获取请求pathinfo路径
     *
     * @return string
     */
    public function path(): string
    {
        return $this->service()->path();
    }

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->service()->uri();
    }

    /**
     * 获取HTTP协议版本号
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        return $this->service()->protocolVersion();
    }

    /**
     * 是否Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '')) === 'xmlhttprequest';
    }

    /**
     * 请求URL
     *
     * @return string
     */
    public function url(): string
    {
        return '//' . $this->host() . $this->path();
    }

    /**
     * 完整的请求URL
     *
     * @return string
     */
    public function fullUrl(): string
    {
        return '//' . $this->host() . $this->uri();
    }

    /**
     * 获取真实IP
     *
     * @param boolean $safe_mode 是否安全模式
     * @return string
     */
    public function ip(bool $safe_mode = true): string
    {
        $remote_ip = $this->getRemoteIp();
        if ($safe_mode && !Tool::isIntranetIp($remote_ip)) {
            return $remote_ip;
        }
        return $this->header('client-ip', $this->header('x-forwarded-for', $this->header('x-real-ip', $this->header('x-client-ip', $this->header('via', $remote_ip)))));
    }

    /**
     * 获得连接的客户端ip
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return $this->connection()->getRemoteIp();
    }

    /**
     * 获得连接的客户端端口
     *
     * @return integer
     */
    public function getRemotePort(): int
    {
        return $this->connection()->getRemotePort();
    }

    /**
     * 获取本地IP
     *
     * @return string
     */
    public function getLocalIp(): string
    {
        return $this->connection()->getLocalIp();
    }

    /**
     * 获取本地端口
     *
     * @return integer
     */
    public function getLocalPort(): int
    {
        return $this->connection()->getLocalPort();
    }

    /**
     * 解析文件
     *
     * @param array $file 文件信息
     * @return UploadFile
     */
    protected function parseFile($file): UploadFile
    {
        return new UploadFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
    }

    /**
     * 解析文件列表
     *
     * @param array $files
     * @return array
     */
    protected function parseFiles(array $files): array
    {
        $upload_files = [];
        foreach ($files as $key => $file) {
            if (is_array(current($file))) {
                $upload_files[$key] = $this->parseFiles($file);
            } else {
                $upload_files[$key] = $this->parseFile($file);
            }
        }
        return $upload_files;
    }

    /**
     * 设置属性，装饰 Workerman的Request对象
     *
     * @param string $name  属性名
     * @param mixed  $value 属性值
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->service()->{$name} = $value;
    }

    /**
     * 获取属性，装饰 Workerman的Request对象
     *
     * @param string $name 属性名
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->service()->{$name};
    }

    /**
     * 魔术方法调用，支持请求实例接口额外支持的方法
     *
     * @param  string $method 方法名
     * @param  array  $params 参数
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function __call(string $method, array $params)
    {
        if (is_callable([$this->service(), $method])) {
            return call_user_func_array([$this->service(), $method], $params);
        }

        throw new InvalidArgumentException("WorkerMan Request method not found => " . $method);
    }

    /**
     * 销毁Request对象
     *
     * @return void
     */
    public function destroy()
    {
        if ($this->service()->context) {
            $this->service()->context  = [];
        }
        if ($this->service()->properties) {
            $this->service()->properties = [];
        }
        $this->service()->connection = null;
    }
}
