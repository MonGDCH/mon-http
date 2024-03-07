<?php

declare(strict_types=1);

namespace mon\http\exception;

use Exception;
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
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response
    {
        $data = [
            'code' => $this->getCode(),
            'msg'  => $this->getMessage(),
            'data' => [],
        ];
        $headers['Content-Type'] = 'application/json;charset=utf-8';
        $result = json_encode($data, JSON_UNESCAPED_UNICODE);
        return new Response(200, $headers, $result);
    }
}
