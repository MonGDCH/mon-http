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
abstract class Controller
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
     * 输出视图内容
     *
     * @param string $content   输出内容
     * @param array $header     响应头
     * @param integer $status   响应状态码
     * @return Response
     */
    protected function view(string $content, array $header = [], int $status = 200): Response
    {
        $headers = array_merge($this->headers, $header);
        return new Response($status, $headers, $content);
    }

    /**
     * 返回错误信息
     *
     * @param string $msg       描述信息
     * @param array  $data      结果集
     * @param array  $extend    扩展数据
     * @param array  $headers   响应头
     * @param integer $status   响应状态码
     * @return Response
     */
    protected function error(string $msg, array $data = [], array $extend = [], array $headers = [], int $status = 200): Response
    {
        return $this->result(0, $msg, $data, $extend, $headers, $status);
    }

    /**
     * 返回成功信息
     *
     * @param string $msg       描述信息
     * @param array  $data      结果集
     * @param array  $extend    扩展数据
     * @param array  $headers   响应头
     * @param integer $status   响应状态码
     * @return Response
     */
    protected function success(string $msg, array $data = [], array $extend = [], array $headers = [], int $status = 200): Response
    {
        return $this->result(1, $msg, $data, $extend, $headers, $status);
    }

    /**
     * 封装response返回
     *
     * @param  integer $code    状态码
     * @param  string  $msg     描述信息
     * @param  array   $data    结果集
     * @param  array   $extend  扩展字段
     * @param  array   $headers 响应头
     * @param  integer $status  响应状态码
     * @return Response
     */
    protected function result(int $code, string $msg, array $data = [], array $extend = [], array $headers = [], int $status = 200): Response
    {
        $headers = array_merge($this->headers, $headers);
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

        return new Response($status, $headers, $data);
    }
}
