<?php

declare(strict_types=1);

namespace mon\worker;

use mon\util\Common;
use mon\util\Instance;
use mon\worker\exception\JumpException;

/**
 * URL构建类
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Url
{
    use Instance;

    /**
     * 服务容器
     *
     * @var Request
     */
    protected $request;

    /**
     * 构造方法
     */
    protected function __construct()
    {
        $this->request = App::instance()->request();
    }

    /**
     * 构建URL
     *
     * @param  string        $url    URL路径
     * @param  string|array  $vars   传参
     * @return string   生成的URL
     */
    public function build(string $url = '', array $vars = []): string
    {
        // $url为空是，采用当前pathinfo
        if (empty($url)) {
            $url = $this->request->path();
        }

        // 判断是否包含域名,解析URL和传参
        if (false === strpos($url, '://') && 0 !== strpos($url, '/')) {
            $info = parse_url($url);
            $url  = !empty($info['path']) ? $info['path'] : '';
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

        // 解析参数
        if (is_string($vars)) {
            // aaa=1&bbb=2 转换成数组
            parse_str($vars, $vars);
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
            $url .= '?' . $vars . $anchor;
        } else {
            $url .= $anchor;
        }

        if (!isset($scheme)) {
            // 补全baseUrl
            $url = '/' . ltrim($url, '/');
        } else {
            $url = $scheme . '://' . $url;
        }

        return $url;
    }

    /**
     * 页面跳转
     * 
     * @param  string  $url    跳转URL
     * @param  integer $code   跳转状态码，默认302
     * @param  array   $header 响应头
     * @throws JumpException
     * @return void
     */
    public function redirect(string $url = '', array $vars = [], int $code = 302, array $header = []): void
    {
        $header['Location'] = $this->build($url, $vars);
        $response = new Response($code, $header);
        throw new JumpException($response);
    }

    /**
     * 返回封装后的API数据到客户端
     * 
     * @param integer   $code       数据集code值
     * @param string    $msg        数据集提示信息
     * @param array     $data       数据集结果集
     * @param array     $extend     或者数据集数据
     * @param string    $type       返回数据类型，默认Json，支持json、xml类型
     * @param integer   $http_code  响应状态码
     * @param array     $header     响应头
     * @throws JumpException
     * @return void
     */
    public function result(int $code = 0, string $msg = '', array $data = [], array $extend = [], string $type = 'json', int $http_code = 200, array $header = []): void
    {
        // 响应数据
        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
        $result = array_merge($result, $extend);
        $charset = 'utf-8';
        switch ($type) {
            case 'xml':
                $header['Content-Type'] = 'text/xml;charset=' . $charset;
                $root = App::instance()->name();
                $data  = "<?xml version=\"1.0\" encoding=\"{$charset}\"?>";
                $data .= "<{$root}>";
                $data .= Common::instance()->arrToXML($result);
                $data .= "</{$root}>";
                break;
            default:
                $header['Content-Type'] = 'application/json;charset=' . $charset;
                $data = json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
        }

        $response = new Response($http_code, $header, $data);
        throw new JumpException($response);
    }

    /**
     * 程序结束
     *
     * @param  integer $code    状态码
     * @param  string  $msg     返回内容
     * @param  array   $header  响应头信息
     * @throws JumpException
     * @return void
     */
    public function abort(int $code, string $msg = null, array $header = []): void
    {
        $response = new Response($code, $header, $msg);
        throw new JumpException($response);
    }
}
