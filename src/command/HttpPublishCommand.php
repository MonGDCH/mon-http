<?php

declare(strict_types=1);

namespace mon\http\Command;

use mon\http\Install;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 发布插件包到Gaia中
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class HttpPublishCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'http:publish';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Publish the vendor package.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'mon-http';

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        Install::publish();
    }
}
