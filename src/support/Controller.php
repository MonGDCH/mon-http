<?php

declare(strict_types=1);

namespace mon\http\support;

use mon\util\Common;
use mon\http\Response;

/**
 * 控制器基类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Controller
{
    /**
     * 返回数据类型
     *
     * @var string
     */
    protected $dataType = 'json';

    /**
     * 响应头
     *
     * @var array
     */
    protected $headers = [];

    /**
     * 允许跨域的域名
     *
     * @var array
     */
    protected $allowOrigin = [];

    /**
     * 允许跨域的请求方式
     *
     * @var array
     */
    protected $allowMethods = [];

    /**
     * 允许跨域的请求头
     *
     * @var array
     */
    protected $allowHeaders = [];

    /**
     * 输出视图内容
     *
     * @param string $content   输出内容
     * @param array $header     响应头
     * @param integer $code     状态码
     * @return Response
     */
    protected function view(string $content, array $header = [], int $code = 200): Response
    {
        return new Response($code, $header, $content);
    }

    /**
     * 返回错误信息
     *
     * @param string $msg       描述信息
     * @param array  $data       结果集
     * @param array  $extend     扩展数据
     * @param array  $headers    响应头
     * @return Response
     */
    protected function error(string $msg, array $data = [], array $extend = [], array $headers = []): Response
    {
        return $this->dataReturn(0, $msg, $data, $extend, $headers);
    }

    /**
     * 返回成功信息
     *
     * @param string $msg       描述信息
     * @param array  $data       结果集
     * @param array  $extend     扩展数据
     * @param array  $headers    响应头
     * @return Response
     */
    protected function success(string $msg, array $data = [], array $extend = [], array $headers = []): Response
    {
        return $this->dataReturn(1, $msg, $data, $extend, $headers);
    }

    /**
     * 封装response返回
     *
     * @param  integer $code    状态码
     * @param  string  $msg     描述信息
     * @param  array   $data    结果集
     * @param  array   $extend  扩展字段
     * @param  array   $headers 响应头
     * @return Response
     */
    protected function dataReturn(int $code, string $msg, array $data = [], array $extend = [], array $headers = []): Response
    {
        $headers = array_merge($this->headers, $headers);
        if (!empty($this->allowOrigin)) {
            $origin = implode(',', (array) $this->allowOrigin);
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        if (!empty($this->allowMethods)) {
            $method = strtoupper(implode(',', (array) $this->allowMethods));
            $headers['Access-Control-Allow-Methods'] = $method;
        }

        if (!empty($this->allowHeaders)) {
            $headers = strtoupper(implode(',', (array) $this->allowHeaders));
            $headers['Access-Control-Allow-Headers'] = $headers;
        }

        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
        $result = array_merge($result, $extend);
        $charset = 'utf-8';
        switch ($this->dataType) {
            case 'xml':
                $headers['Content-Type'] = 'text/xml;charset=' . $charset;
                $root = 'mon';
                $data  = "<?xml version=\"1.0\" encoding=\"{$charset}\"?>";
                $data .= "<{$root}>";
                $data .= Common::instance()->arrToXML($result);
                $data .= "</{$root}>";
                break;
            default:
                $headers['Content-Type'] = 'application/json;charset=' . $charset;
                $data = json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
        }

        return new Response(200, $headers, $data);
    }
}
