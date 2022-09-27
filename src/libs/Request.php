<?php

declare(strict_types=1);

namespace mon\http\libs;

/**
 * 请求实例，公共trait
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
trait Request
{
    /**
     * 控制器
     *
     * @var string
     */
    public $controller = '';

    /**
     * 控制器回调方法
     *
     * @var string
     */
    public $action = '';

    /**
     * 获取控制器名称
     *
     * @return string
     */
    public function controller(): string
    {
        return $this->controller;
    }

    /**
     * 获取控制器回调方法名称
     *
     * @return string
     */
    public function action(): string
    {
        return $this->action;
    }


    /**
     * 构建生成URL
     *
     * @param string $url URL路径
     * @param array $vars 传参
     * @return string
     */
    public function buildURL(string $url = '', array $vars = []): string
    {
        // $url为空是，采用当前pathinfo
        if (empty($url)) {
            $url = $this->path();
        }

        // 判断是否包含域名,解析URL和传参
        if (strpos($url, '://') === false && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = $info['path'] ?: '';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (strpos($anchor, '?') !== false) {
                    // 解析参数
                    list($anchor, $info['query']) = explode('?', $anchor, 2);
                }
            }
        } elseif (strpos($url, '://') !== false) {
            // 存在协议头，自带domain
            $info = parse_url($url);
            $url  = $info['host'];
            $scheme = isset($info['scheme']) ? $info['scheme'] : 'http';
            // 判断是否存在锚点,解析请求串
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (strpos($anchor, '?') !== false) {
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
     * 是否GET请求
     *
     * @return boolean
     */
    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    /**
     * 是否POST请求
     *
     * @return boolean
     */
    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * 是否PUT请求
     *
     * @return boolean
     */
    public function isPut(): bool
    {
        return $this->method() === 'PUT';
    }

    /**
     * 是否DELETE请求
     *
     * @return boolean
     */
    public function isDelete(): bool
    {
        return $this->method() === 'DELETE';
    }

    /**
     * 是否PATCH请求
     *
     * @return boolean
     */
    public function isPatch(): bool
    {
        return $this->method() === 'PATCH';
    }

    /**
     * 是否HEAD请求
     *
     * @return boolean
     */
    public function isHead(): bool
    {
        return $this->method() === 'HEAD';
    }

    /**
     * 是否OPTIONS请求
     *
     * @return boolean
     */
    public function isOptions(): bool
    {
        return $this->method() === 'OPTIONS';
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
    protected function getData(array $data, string $name, $default = null)
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
}
