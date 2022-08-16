<?php

declare(strict_types=1);

namespace mon\http;

use mon\util\Common;
use mon\util\Instance;
use mon\http\exception\JumpException;

/**
 * 业务跳转
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Jump
{
    use Instance;

    /**
     * 页面跳转
     * 
     * @param  string  $url    跳转URL
     * @param  integer $code   跳转状态码，默认302
     * @param  array   $header 响应头
     * @throws JumpException
     * @return void
     */
    public function redirect(string $url = '', array $vars = [], int $code = 302, array $header = [])
    {
        $header['Location'] = App::instance()->request()->build($url, $vars);
        $response = new Response($code, $header);
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
    public function abort(int $code, string $msg = null, array $header = [])
    {
        $response = new Response($code, $header, $msg);
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
    public function result(int $code = 0, string $msg = '', array $data = [], array $extend = [], string $type = 'json', int $http_code = 200, array $header = [])
    {
        // 响应数据
        $result = ['code' => $code, 'msg' => $msg, 'data' => $data];
        $result = array_merge($result, $extend);
        $charset = 'utf-8';
        switch ($type) {
            case 'xml':
                $header['Content-Type'] = 'text/xml;charset=' . $charset;
                $root = 'mon';
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
}
