<?php

declare(strict_types=1);

namespace mon\worker;

use mon\worker\libs\UploadFile;
use Workerman\Protocols\Http\Request as HttpRequest;

/**
 * 请求处理
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Request extends HttpRequest
{
    /**
     * 构建生成URL
     *
     * @param string $url URL路径
     * @param array $vars 传参
     * @return string
     */
    public function build(string $url = '', array $vars = []): string
    {
        // $url为空是，采用当前pathinfo
        if (empty($url)) {
            $url = $this->path();
        }

        // 判断是否包含域名,解析URL和传参
        if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = empty($info['path']) ?: '';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (false !== strpos($anchor, '?')) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        } elseif (false !== strpos($url, '://')) {
            // 存在协议头，自带domain
            $info = parse_url($url);
            $url  = $info['host'];
            $scheme = isset($info['scheme']) ? $info['scheme'] : 'http';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (false !== strpos($anchor, '?')) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        }

        // 判断是否已传入URL,且URl中携带传参, 解析传参到$vars中
        if ($url && isset($info['query'])) {
            // 解析地址里面参数 合并到vars
            parse_str($info['query'], $params);
            $vars = array_merge($params, $vars);
            unset($info['query']);
        }

        // 还原锚点
        $anchor = !empty($anchor) ? '#' . $anchor : '';
        // 组装传参
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '?' . $vars;
        }
        $url .= $anchor;

        if (!isset($scheme)) {
            // 补全baseUrl
            $url = '/' . ltrim($url, '/');
        } else {
            $url = $scheme . '://' . $url;
        }

        return $url;
    }

    /**
     * 获取GET数据
     *
     * @param string|null $name 参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get($name = null, $default = null, $filter = true)
    {
        $result = parent::get($name, $default);
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
    public function post($name = null, $default = null, $filter = true)
    {
        $result = parent::post($name, $default);
        return $filter && $result ? $this->filter($result) : $result;
    }

    /**
     * 获取application/json参数
     *
     * @param string|null $name 参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function json($name = null, $default = null, $filter = true)
    {
        $data = (array)json_decode($this->rawBody(), true);
        $result = is_null($name) ? $data : $this->getData($data, $name, $default);

        return $filter && $data ? $this->filter($result) : $result;
    }

    /**
     * 获取上传文件
     *
     * @param string|null $name 文件名
     * @return null|UploadFile[]|UploadFile
     */
    public function file($name = null)
    {
        $files = parent::file($name);
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
     * 是否GET请求
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET' ? true : false;
    }

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST' ? true : false;
    }

    /**
     * 是否PUT请求
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT' ? true : false;
    }

    /**
     * 是否DELETE请求
     *
     * @return boolean
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE' ? true : false;
    }

    /**
     * 是否PATCH请求
     *
     * @return boolean
     */
    public function isPatch(): bool
    {
        return $this->method() === 'PATCH' ? true : false;
    }

    /**
     * 是否HEAD请求
     *
     * @return boolean
     */
    public function isHead(): bool
    {
        return $this->method() === 'HEAD' ? true : false;
    }

    /**
     * 是否OPTIONS请求
     *
     * @return boolean
     */
    public function isOptions(): bool
    {
        return $this->method() === 'OPTIONS' ? true : false;
    }

    /**
     * 是否Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
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
     * 获得连接的客户端ip
     *
     * @return string
     */
    public function getRemoteIp(): string
    {
        return App::instance()->connection()->getRemoteIp();
    }

    /**
     * 获得连接的客户端端口
     *
     * @return integer
     */
    public function getRemotePort(): int
    {
        return App::instance()->connection()->getRemotePort();
    }

    /**
     * 获取本地IP
     *
     * @return string
     */
    public function getLocalIp(): string
    {
        return App::instance()->connection()->getLocalIp();
    }

    /**
     * 获取本地断开
     *
     * @return integer
     */
    public function getLocalPort(): int
    {
        return App::instance()->connection()->getLocalPort();
    }

    /**
     * 获取真实IP
     *
     * @param boolean $safe_mode 是否安全模式
     * @return string
     */
    public function getRealIp($safe_mode = true): string
    {
        $remote_ip = $this->getRemoteIp();
        if ($safe_mode && !$this->isIntranetIp($remote_ip)) {
            return $remote_ip;
        }
        return $this->header('client-ip', $this->header(
            'x-forwarded-for',
            $this->header('x-real-ip', $this->header(
                'x-client-ip',
                $this->header('via', $remote_ip)
            ))
        ));
    }

    /**
     * 判断是否为内容IP
     *
     * @param string $ip
     * @return boolean
     */
    public function isIntranetIp($ip): bool
    {
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
     * 数据安全过滤，采用htmlspecialchars函数
     * 
     * @param  string|array $input 过滤的数据
     * @return mixed
     */
    public function filter($input)
    {
        if (is_array($input)) {
            return array_map('htmlspecialchars', (array)$input);
        }

        return htmlspecialchars($input);
    }

    /**
     * 获取数据, 支持通过'.'分割获取无限级节点数据
     *
     * @param  array  $data 数据源
     * @param  string $name 字段名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    protected function getData(array $data, $name, $default = null)
    {
        foreach (explode('.', $name) as $val) {
            if (isset($data[$val])) {
                $data = $data[$val];
            } else {
                return $default;
            }
        }

        return $data;
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
