<?php

declare(strict_types=1);

namespace mon\http\fpm;

use mon\util\Instance;
use mon\http\libs\Request as LibsRequest;
use mon\http\interfaces\RequestInterface;

/**
 * FPM请求类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Request implements RequestInterface
{
    use Instance, LibsRequest;

    /**
     * HTTP请求头
     *
     * @var array
     */
    protected $header = [];

    /**
     * php://input数据
     *
     * @var string
     */
    protected $input = null;

    /**
     * 构造方法
     */
    public function __construct()
    {
        if (function_exists('apache_request_headers') && $result = apache_request_headers()) {
            $header = $result;
        } else {
            $header = [];
            foreach ($_SERVER as $key => $val) {
                if (0 === strpos($key, 'HTTP_')) {
                    $key = str_replace('_', '-', strtolower(substr($key, 5)));
                    $header[$key] = $val;
                }
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $header['content-type'] = $_SERVER['CONTENT_TYPE'];
            }
            if (isset($_SERVER['CONTENT_LENGTH'])) {
                $header['content-length'] = $_SERVER['CONTENT_LENGTH'];
            }
        }

        $this->header = array_change_key_case($header);
        $this->input = file_get_contents('php://input');
    }

    /**
     * 获取GET数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function get($name = null, $default = null, bool $filter = true)
    {
        $result = is_null($name) ? $_GET : $this->getData($_GET, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取POST数据
     *
     * @param mixed  $name      参数键名
     * @param mixed  $default   默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function post($name = null, $default = null, bool $filter = true)
    {
        $result = is_null($name) ? $_POST : $this->getData($_POST, $name, $default);

        return $filter ? $this->filter($result) : $result;
    }

    /**
     * 获取application/json参数
     *
     * @param mixed $name       参数键名
     * @param mixed $default    默认值
     * @param boolean $filter   是否过滤参数
     * @return mixed
     */
    public function json($name = null, $default = null, bool $filter = true)
    {
        $data = (array)json_decode($this->input, true);
        $result = is_null($name) ? $data : $this->getData($data, $name, $default);

        return $filter && $data ? $this->filter($result) : $result;
    }

    /**
     * 获取header信息
     *
     * @param mixed $name    参数键名
     * @param mixed $default 默认值
     * @return mixed
     */
    public function header($name = null, $default = null)
    {
        if (is_null($$name)) {
            return $this->header;
        }

        $name = str_replace('_', '-', strtolower($name));
        return isset($this->header[$name]) ? $this->header[$name] : $default;
    }

    /**
     * 获取$_SERVER数据
     *
     * @param  mixed  $name    参数键名
     * @param  mixed  $default 默认值
     * @return mixed
     */
    public function server($name = null, $default = null)
    {
        return is_null($name) ? $_SERVER : $this->getData($_SERVER, $name, $default);
    }

    /**
     * 获取请求Session
     *
     * @return mixed
     */
    public function session($name = null, $default = null)
    {
        $value = session_status() == PHP_SESSION_ACTIVE ? $_SESSION : [];
        return is_null($name) ? $value : $this->getData($value, $name, $default);
    }

    /**
     * 获取请求Cookie
     *
     * @return mixed
     */
    public function cookie($name = null, $default = null)
    {
        return is_null($name) ? $_COOKIE : $this->getData($_COOKIE, $name, $default);
    }

    /**
     * 获取上传文件
     *
     * @param mixed $name 文件参数名
     * @return mixed
     */
    public function file($name = null)
    {
        if (is_null($name)) {
            return $_FILES;
        }

        return $_FILES[$name] ?? null;
    }

    /**
     * 获取请求类型
     *
     * @return string
     */
    public function method(): string
    {
        return $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * 获取请求host
     *
     * @return string
     */
    public function host(): string
    {
        return $this->server('HTTP_HOST', '');
    }

    /**
     * 获取请求pathinfo路径
     *
     * @return string
     */
    public function path(): string
    {
        $pathInfo = $this->detectPathInfo();
        return $pathInfo ? preg_replace('/[\/]+/', '/', $pathInfo) : '/';
    }

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function uri(): string
    {
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            // 带微软重写模块的IIS
            $requestUri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            // 带ISAPI_Rewrite的IIS
            $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['IIS_WasUrlRewritten']) && $_SERVER['IIS_WasUrlRewritten'] == '1' && isset($_SERVER['UNENCODED_URL']) && $_SERVER['UNENCODED_URL'] != '') {
            // URL重写的IIS7：确保我们得到的未编码的URL(双斜杠的问题)
            $requestUri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            // 只使用URL路径, 不包含scheme、主机[和端口]或者http代理
            if ($requestUri) {
                $requestUri = preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            // IIS 5.0, CGI
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $requestUri = '/';
        }

        return $requestUri;
    }

    /**
     * 获取当前请求的域名
     *
     * @return string
     */
    public function url(): string
    {
        return '//' . $this->host() . $this->path();
    }

    /**
     * 请求完整URL
     *
     * @return string
     */
    public function fullUrl(): string
    {
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $url = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $url = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        } else {
            $url = '';
        }

        return '//' . $this->host() . $url;
    }

    /**
     * 获取真实IP
     *
     * @return string
     */
    public function ip(): string
    {
        foreach (['X_FORWARDED_FOR', 'HTTP_X_FORWARDED_FOR', 'CLIENT_IP', 'REMOTE_ADDR'] as $key) {
            if (isset($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        return '';
    }

    /**
     * 获取HTTP协议版本号
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        $version = $this->server('SERVER_PROTOCOL', '');
        $protoco_version = substr(strstr($version, 'HTTP/'), 5);
        return $protoco_version ?: '1.0';
    }

    /**
     * 是否Ajax
     *
     * @return boolean
     */
    public function isAjax(): bool
    {
        $value = $this->server('HTTP_X_REQUESTED_WITH', '');
        return strtolower($value) == 'xmlhttprequest';
    }

    /**
     * 检测 baseURL 和查询字符串之间的 PATH_INFO
     *
     * @return string
     */
    protected function detectPathInfo(): string
    {
        // 如果已经包含 PATH_INFO
        if (!empty($_SERVER['PATH_INFO'])) {
            return $_SERVER['PATH_INFO'];
        }
        if (($requestUri = $this->uri()) == '/') {
            return '';
        }

        $baseUrl = $this->detectBaseUrl();
        $baseUrlEncoded = rtrim($baseUrl, '/');

        if ($pos = strpos($requestUri, '?')) {
            $requestUri = substr($requestUri, 0, $pos);
        }

        if (!empty($baseUrl)) {
            if (strpos($requestUri, $baseUrl) === 0) {
                $pathInfo = substr($requestUri, strlen($baseUrl));
            } elseif (strpos($requestUri, $baseUrlEncoded) === 0) {
                $pathInfo = substr($requestUri, strlen($baseUrlEncoded));
            } else {
                $pathInfo = $requestUri;
            }
        } else {
            $pathInfo = $requestUri;
        }

        return $pathInfo;
    }

    /**
     * 自动检测从请求环境的基本 URL
     * 采用了多种标准, 以检测请求的基本 URL
     *
     * @return string
     */
    protected function detectBaseUrl(): string
    {
        $baseUrl        = '';
        $fileName       = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $scriptName     = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
        $phpSelf        = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null;
        $origScriptName = isset($_SERVER['ORIG_SCRIPT_NAME']) ? $_SERVER['ORIG_SCRIPT_NAME'] : null;

        if ($scriptName !== null && basename($scriptName) === $fileName) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $fileName) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $fileName) {
            $baseUrl = $origScriptName;
        } else {
            $baseUrl  = '/';
            $basename = basename($fileName);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, (int)strpos($path, $basename)) . $basename;
            }
        }

        // 请求的URI
        $requestUri = $this->uri();
        // 与请求的URI一样?
        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $basename = basename($baseUrl);
        if (empty($basename)) {
            return '';
        }

        if (strlen($requestUri) >= strlen($baseUrl) && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }
}
