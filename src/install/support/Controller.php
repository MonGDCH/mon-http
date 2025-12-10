<?php

declare(strict_types=1);

namespace support\http;

use mon\util\Common;
use mon\http\Response;
use mon\util\Validate;
use mon\util\Container;
use mon\http\interfaces\RequestInterface;

/**
 * 控制器基类
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
abstract class Controller
{
    /**
     * 响应头
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * 返回数据类型
     *
     * @var string
     */
    protected string $dataType = 'json';

    /**
     * 成功响应code值
     *
     * @var integer
     */
    protected int $success_code = 1;

    /**
     * 错误响应code值
     *
     * @var integer
     */
    protected int $error_code = 0;

    /**
     * 验证器驱动，默认为内置的Validate验证器
     *
     * @var string
     */
    protected string $validate = Validate::class;

    /**
     * 获取实例化验证器
     *
     * @return Validate
     */
    public function validate(): Validate
    {
        return Container::instance()->get($this->validate);
    }

    /**
     * 输出文本内容
     *
     * @param string $content   输出内容
     * @param array $header     响应头
     * @param integer $status   响应状态码
     * @return Response
     */
    protected function text(string $content, array $header = [], int $status = 200): Response
    {
        $headers = array_merge($this->headers, $header);
        return new Response($status, $headers, $content);
    }

    /**
     * 输出json内容
     *
     * @param array $data       结果集
     * @param array $header     响应头
     * @param integer $status   响应状态码
     * @return Response
     */
    protected function json(array $data, array $header = [], int $status = 200): Response
    {
        $headers = array_merge($this->headers, $header);
        $headers['Content-Type'] = 'application/json;charset=utf-8';
        return new Response($status, $headers, json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 输出文件流
     *
     * @param string $path  文件路径
     * @param RequestInterface|null $request    请求实例
     * @return Response
     */
    protected function file(string $path, RequestInterface $request = null): Response
    {
        return (new Response())->file($path, $request);
    }

    /**
     * 文件下载
     *
     * @param string $path  文件路径
     * @param string $name  文件名
     * @return Response
     */
    protected function download(string $path, string $name): Response
    {
        return (new Response())->download($path, $name);
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
        return $this->result($this->error_code, $msg, $data, $extend, $headers, $status);
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
        return $this->result($this->success_code, $msg, $data, $extend, $headers, $status);
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
                $data  = "<?xml version=\"1.0\" encoding=\"{$charset}\"?>";
                $data .= "<xml>";
                $data .= Common::arrToXML($result);
                $data .= "</xml>";
                break;
            default:
                $headers['Content-Type'] = 'application/json;charset=' . $charset;
                $data = json_encode($result, JSON_UNESCAPED_UNICODE);
                break;
        }

        return new Response($status, $headers, $data);
    }
}
