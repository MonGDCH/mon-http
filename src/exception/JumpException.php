<?php

declare(strict_types=1);

namespace mon\http\exception;

use Exception;
use mon\http\Response;
use mon\http\interfaces\RequestInterface;
use mon\http\interfaces\BusinessInterface;

/**
 * 业务跳转URL
 *
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class JumpException extends Exception implements BusinessInterface
{
    /**
     * 构造方法
     *
     * @param string $url   跳转地址，code为302有效
     * @param integer $code 响应状态码
     */
    public function __construct(string $url = '', int $code = 302)
    {
        parent::__construct($url, $code);
    }

    /**
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response
    {
        if ($this->getCode() == 302) {
            $header['Location'] = $this->getMessage();
            return new Response($this->getCode(), $header);
        }

        return new Response($this->getCode());
    }
}
