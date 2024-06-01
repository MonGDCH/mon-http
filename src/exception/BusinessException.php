<?php

declare(strict_types=1);

namespace mon\http\exception;

use Exception;
use mon\util\Common;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\BusinessInterface;

/**
 * 业务异常，返回输出json数据
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class BusinessException extends Exception implements BusinessInterface
{
    /**
     * 返回数据类型
     *
     * @var string
     */
    protected $dataType = 'json';

    /**
     * 响应数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 构造方法
     *
     * @param string $message   描述信息
     * @param integer $code     状态码
     * @param array $data       结果就
     * @param string $dataType  响应数据类型
     */
    public function __construct(string $message = '', int $code = 0, array $data = [], string $dataType = 'json')
    {
        parent::__construct($message, $code);
        $this->data = $data;
        $this->dataType = $dataType;
    }

    /**
     * 获取数据类型
     *
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * 获取响应数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response
    {
        $result = [
            'code' => $this->getCode(),
            'msg'  => $this->getMessage(),
            'data' => $this->getData(),
        ];
        $charset = 'utf-8';
        switch ($this->getDataType()) {
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
