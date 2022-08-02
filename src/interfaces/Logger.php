<?php

declare(strict_types=1);

namespace mon\worker\interfaces;

use Psr\Log\LoggerInterface;

/**
 * 日志记录接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Logger 
{
    /**
     * 保存日志
     *
     * @return mixed
     */
    public function save();
}
