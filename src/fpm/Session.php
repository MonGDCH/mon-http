<?php

declare(strict_types=1);

namespace mon\http\fpm;

use mon\util\Instance;

/**
 * Session工具
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session
{
    use Instance;

    /**
     * 配置信息
     *
     * @var array
     */
    protected $config = [
        // session名称，默认：PHPSID
        'session_name'      => 'PHPSID',
        // cookie有效期，默认：1440
        'cookie_lifetime'   => 1440,
        // cookie路径，默认：/
        'cookie_path'       => '/',
        // 同站点cookie，默认：''
        'same_site'         => '',
        // cookie的domain，默认：''
        'domain'            => '',
        // 是否仅适用https的cookie，默认：false
        'secure'            => false,
        // session有效期，默认：1440
        'lifetime'          => 1440,
        // 是否开启http_only，默认：true
        'http_only'         => true,
        // gc的概率，默认：[1, 1000]
        'gc_probability'    => [1, 1000],
    ];

    /**
     * 标记初始化
     *
     * @var null
     */
    protected $init = null;

    /**
     * 私有话构造方法
     *
     * @param array $config
     */
    protected function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 注册初始化session配置
     *
     * @param array $config 配置信息
     * @return Session
     */
    public function register(array $config = []): Session
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }

        $isDoStart = false;
        // 判断是否在php.ini中开启是否已开启session
        if (PHP_SESSION_ACTIVE != session_status()) {
            // 未开启，关闭php.ini的自动开启
            ini_set('session.auto_start', '0');
            $isDoStart = true;

            // 设置session名称
            if (isset($this->config['session_name']) && !empty($this->config['session_name'])) {
                session_name($this->config['session_name']);
            }
            // 设置session有效期
            if (isset($this->config['lifetime']) && !empty($this->config['lifetime'])) {
                ini_set('session.gc_maxlifetime', (string)$this->config['lifetime']);
            }
            if (isset($this->config['cookie_lifetime']) && !empty($this->config['cookie_lifetime'])) {
                ini_set('session.cookie_lifetime', (string)$this->config['cookie_lifetime']);
            }
            // cookie domain
            if (isset($this->config['domain']) && !empty($this->config['domain'])) {
                ini_set('session.cookie_domain', $this->config['domain']);
            }
            // 同站点cookie
            if (isset($this->config['same_site']) && !empty($this->config['same_site'])) {
                ini_set('session.cookie_samesite', $this->config['same_site']);
            }
            // cookie路径，默认：/
            if (isset($this->config['cookie_path']) && !empty($this->config['cookie_path'])) {
                ini_set('session.cookie_path', $this->config['cookie_path']);
            }
            // session安全传输
            if (isset($this->config['secure']) && !empty($this->config['secure'])) {
                ini_set('session.cookie_secure', $this->config['secure'] ? '1' : '0');
            }
            // httponly设置
            if (isset($this->config['http_only']) && !empty($this->config['http_only'])) {
                ini_set('session.cookie_httponly', $this->config['http_only'] ? '1' : '0');
            }
            // gc
            if (isset($this->config['gc_probability']) && !empty($this->config['gc_probability']) && is_array($this->config['gc_probability'])) {
                ini_set('session.gc_probability', (string)$this->config['gc_probability'][0]);
                ini_set('session.gc_divisor', (string)$this->config['gc_probability'][1]);
            }
        }

        // 初始化
        if ($isDoStart) {
            session_start();
            $this->init = true;
        } else {
            $this->init = false;
        }

        return $this;
    }

    /**
     * session自动启动或者初始化
     *
     * @return Session
     */
    public function bootstrap(): Session
    {
        if (is_null($this->init)) {
            $this->register();
        } elseif (false === $this->init) {
            if (PHP_SESSION_ACTIVE != session_status()) {
                session_start();
            }
            $this->init = true;
        }

        return $this;
    }

    /**
     * 设置session, 支持.二级设置
     *
     * @param string $key   键名
     * @param mixed $value  键值
     * @return Session
     */
    public function set(string $key, $value = null): Session
    {
        empty($this->init) && $this->bootstrap();
        if (strpos($key, '.')) {
            // 二维数组赋值
            list($name1, $name2) = explode('.', $key, 2);
            $_SESSION[$name1][$name2] = $value;
        } else {
            $_SESSION[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取session值，支持.无限级获取值
     *
     * @param string $key       键名
     * @param mixed  $default   默认值
     * @param string $prefix    前缀
     * @return mixed
     */
    public function get(string $key = '', $default = null)
    {
        empty($this->init) && $this->bootstrap();
        if (empty($key)) {
            return $_SESSION;
        } else {
            $keys = explode('.', $key);
            $value = $_SESSION;
            foreach ($keys as $val) {
                if (isset($value[$val])) {
                    $value = $value[$val];
                } else {
                    $value = $default;
                    break;
                }
            }
            return $value;
        }
    }

    /**
     * 判断session是否存在，支持.无限级判断
     *
     * @param  string  $key 键名
     * @return boolean
     */
    public function has(string $key): bool
    {
        empty($this->init) && $this->bootstrap();
        $keys = explode('.', $key);
        $value = $_SESSION;
        foreach ($keys as $val) {
            if (!isset($value[$val])) {
                return false;
            } else {
                $value = $value[$val];
            }
        }

        return true;
    }

    /**
     * 删除session，支持数组批量删除，支持.二级删除
     *
     * @param  string|array $key 键名
     * @return void
     */
    public function delete($key): void
    {
        empty($this->init) && $this->bootstrap();
        if (is_array($key)) {
            foreach ($key as $name) {
                $this->delete($name);
            }
        } elseif (strpos($key, '.')) {
            // 二维数组赋值
            list($name1, $name2) = explode('.', $key, 2);
            $_SESSION[$name1][$name2] = null;
            unset($_SESSION[$name1][$name2]);
        } else {
            $_SESSION[$key] = null;
            unset($_SESSION[$key]);
        }
    }

    /**
     * 清空Session
     *
     * @return void
     */
    public function clear(): void
    {
        empty($this->init) && $this->bootstrap();
        $_SESSION = [];
    }
}
