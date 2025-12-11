<?php

declare(strict_types=1);

namespace mon\http\workerman;

use RuntimeException;
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
     * 设置属性，装饰 Workerman的Request对象
     *
     * @param string $name  属性名
     * @param mixed  $value 属性值
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->service()->$name = $value;
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
     * @param string|null $name 键名，null 返回session实例，'' 返回所有session数据
     * @param mixed $default    默认值
     * @return mixed
     */
    public function session(?string $name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->service()->session();
        }
        if ($name === '') {
            return $this->service()->session()->all();
        }
        return $this->service()->session()->get($name, $default);
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
        if ($safe_mode && !$this->isIntranetIp($remote_ip)) {
            return $remote_ip;
        }
        return $this->header('client-ip', $this->header(
            'x-forwarded-for',
            $this->header('x-real-ip', $this->header('x-client-ip', $this->header('via', $remote_ip)))
        ));
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
     * 判断是否为内网IP
     *
     * @param string $ip
     * @return boolean
     */
    public function isIntranetIp(string $ip = ''): bool
    {
        $ip = $ip ?: $this->getRemoteIp();
        // Not validate ip .
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        // Is intranet ip ? For IPv4, the result of false may not be accurate, so we need to check it manually later .
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return true;
        }
        // Manual check only for IPv4 .
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        // Manual check .
        $reserved_ips = [
            1681915904 => 1686110207, // 100.64.0.0 -  100.127.255.255
            3221225472 => 3221225727, // 192.0.0.0 - 192.0.0.255
            3221225984 => 3221226239, // 192.0.2.0 - 192.0.2.255
            3227017984 => 3227018239, // 192.88.99.0 - 192.88.99.255
            3323068416 => 3323199487, // 198.18.0.0 - 198.19.255.255
            3325256704 => 3325256959, // 198.51.100.0 - 198.51.100.255
            3405803776 => 3405804031, // 203.0.113.0 - 203.0.113.255
            3758096384 => 4026531839, // 224.0.0.0 - 239.255.255.255
        ];
        $ip_long = ip2long($ip);
        foreach ($reserved_ips as $ip_start => $ip_end) {
            if (($ip_long >= $ip_start) && ($ip_long <= $ip_end)) {
                return true;
            }
        }
        return false;
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
}
