<?php

declare(strict_types=1);

namespace mon\http\fpm;

use mon\http\interfaces\SessionInterface;

/**
 * Session工具
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Session implements SessionInterface
{
    /**
     * 配置信息
     *
     * @var array
     */
    protected static array $config = [
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
    protected ?bool $init = null;

    /**
     * 注册session配置
     *
     * @param array $config
     * @return void
     */
    public static function register(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * session自动启动或者初始化
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if (is_null($this->init)) {
            $this->init();
        } elseif (false === $this->init) {
            if (PHP_SESSION_ACTIVE != session_status()) {
                session_start();
            }
            $this->init = true;
        }
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
     * @return mixed
     */
    public function get(string $key = '', mixed $default = null): mixed
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
     * @param  string $key 键名
     * @return void
     */
    public function delete(string $key): void
    {
        empty($this->init) && $this->bootstrap();
        if (strpos($key, '.')) {
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

    /**
     * 初始化session
     *
     * @return void
     */
    protected function init(): void
    {
        $isDoStart = false;
        // 判断是否在php.ini中开启是否已开启session
        if (PHP_SESSION_ACTIVE != session_status()) {
            // 未开启，关闭php.ini的自动开启
            ini_set('session.auto_start', '0');
            $isDoStart = true;

            // 设置session名称
            if (isset(static::$config['session_name']) && !empty(static::$config['session_name'])) {
                session_name(static::$config['session_name']);
            }
            // 设置session有效期
            if (isset(static::$config['lifetime']) && !empty(static::$config['lifetime'])) {
                ini_set('session.gc_maxlifetime', (string)static::$config['lifetime']);
            }
            if (isset(static::$config['cookie_lifetime']) && !empty(static::$config['cookie_lifetime'])) {
                ini_set('session.cookie_lifetime', (string)static::$config['cookie_lifetime']);
            }
            // cookie domain
            if (isset(static::$config['domain']) && !empty(static::$config['domain'])) {
                ini_set('session.cookie_domain', static::$config['domain']);
            }
            // 同站点cookie
            if (isset(static::$config['same_site']) && !empty(static::$config['same_site'])) {
                ini_set('session.cookie_samesite', static::$config['same_site']);
            }
            // cookie路径，默认：/
            if (isset(static::$config['cookie_path']) && !empty(static::$config['cookie_path'])) {
                ini_set('session.cookie_path', static::$config['cookie_path']);
            }
            // session安全传输
            if (isset(static::$config['secure']) && !empty(static::$config['secure'])) {
                ini_set('session.cookie_secure', static::$config['secure'] ? '1' : '0');
            }
            // httponly设置
            if (isset(static::$config['http_only']) && !empty(static::$config['http_only'])) {
                ini_set('session.cookie_httponly', static::$config['http_only'] ? '1' : '0');
            }
            // gc
            if (isset(static::$config['gc_probability']) && !empty(static::$config['gc_probability']) && is_array(static::$config['gc_probability'])) {
                ini_set('session.gc_probability', (string)static::$config['gc_probability'][0]);
                ini_set('session.gc_divisor', (string)static::$config['gc_probability'][1]);
            }
        }

        // 初始化
        if ($isDoStart) {
            session_start();
            $this->init = true;
        } else {
            $this->init = false;
        }
    }
}
