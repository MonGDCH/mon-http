<?php

declare(strict_types=1);

namespace mon\http\interfaces;

use mon\http\Response;

/**
 * HTTP业务流程控制对象接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface BusinessInterface
{
    /**
     * 获取响应信息
     *
     * @param RequestInterface $request
     * @return Response
     */
    public function getResponse(RequestInterface $request): Response;
}
