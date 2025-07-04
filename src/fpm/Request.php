<?php

declare(strict_types=1);

namespace mon\http\fpm;

use mon\http\libs\UploadFile;
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
    use LibsRequest;

    /**
     * HTTP请求头
     *
     * @var array
     */
    protected $header = null;

    /**
     * php://input数据
     *
     * @var string
     */
    protected $input = null;

    /**
     * 请求uri
     *
     * @var string
     */
    protected $uri = null;

    /**
     * 请求pathinfo
     *
     * @var string
     */
    protected $pathinfo = null;

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
     * 获取input内容
     *
     * @return string
     */
    public function rawBody(): string
    {
        if (is_null($this->input)) {
            $this->input = file_get_contents('php://input');
        }

        return $this->input ?: '';
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
        if (is_null($this->header)) {
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
        }

        if (is_null($name)) {
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
     * @param string|null $name 键名
     * @param mixed $default    默认值
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
     * @param string|null $name cookie名
     * @param mixed $default    默认值
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
        if (empty($_FILES)) {
            return is_null($name) ? [] : null;
        }

        if (!is_null($name)) {
            $files = $_FILES[$name];
            // 多文件
            if (is_array(current($files))) {
                return $this->parseFiles($files);
            }

            return $this->parseFile($files);
        }

        $upload_files = [];
        foreach ($_FILES as $name => $file) {
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
        return $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_SERVER['REQUEST_METHOD'] ?? '';
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
        if (!is_null($this->pathinfo)) {
            return $this->pathinfo;
        }

        $pathInfo = $this->detectPathInfo();
        $pathInfo ? preg_replace('/[\/]+/', '/', $pathInfo) : '/';
        $this->pathinfo = (strpos($pathInfo, '/') !== 0) ? ('/' . $pathInfo) : $pathInfo;
        return $this->pathinfo;
    }

    /**
     * 获取请求URI
     *
     * @return string
     */
    public function uri(): string
    {
        if (!is_null($this->uri)) {
            return $this->uri;
        }

        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            // 带微软重写模块的IIS
            $this->uri = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            // 带ISAPI_Rewrite的IIS
            $this->uri = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['IIS_WasUrlRewritten']) && $_SERVER['IIS_WasUrlRewritten'] == '1' && isset($_SERVER['UNENCODED_URL']) && $_SERVER['UNENCODED_URL'] != '') {
            // URL重写的IIS7：确保我们得到的未编码的URL(双斜杠的问题)
            $this->uri = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $this->uri = $_SERVER['REQUEST_URI'];
            // 只使用URL路径, 不包含scheme、主机[和端口]或者http代理
            if ($this->uri) {
                $this->uri = preg_replace('#^[^/:]+://[^/]+#', '', $this->uri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            // IIS 5.0, CGI
            $this->uri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $this->uri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            $this->uri = '/';
        }

        return $this->uri;
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
        return strtolower($this->server('HTTP_X_REQUESTED_WITH', '')) == 'xmlhttprequest';
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

    /**
     * 解析文件
     *
     * @param array $file 文件信息
     * @return UploadFile
     */
    protected function parseFile(array $file): UploadFile
    {
        return new UploadFile($file['tmp_name'], $file['name'], $file['type'], $file['error']);
    }

    /**
     * 解析文件列表
     *
     * @param array $files 文件信息列表
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
