<?php

declare(strict_types=1);

namespace mon\worker\support;

use mon\util\Log as UtilLog;
use Psr\Log\LoggerInterface;

/**
 * 日志服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Log extends UtilLog implements LoggerInterface
{
}
