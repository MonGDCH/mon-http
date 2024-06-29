<?php

declare(strict_types=1);

namespace support\command\http;

use mon\util\File;
use mon\env\Config;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 清空缓存路由表
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class RouteClearCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'route:clear';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Clear the fpm route cache.';

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
        // 缓存路由信息
        $cache_route = Config::instance()->get('http.app.fpm.route.cache', '');
        if (!$cache_route) {
            return $out->block('Route cache file path error!', 'ERROR');
        }
        $del = File::instance()->removeFile($cache_route);
        if (!$del) {
            return $out->block('Clear route cache error!', 'ERROR');
        }

        return $out->block('Clear route cache success!', 'SUCCESS');
    }
}
