<?php

declare(strict_types=1);

namespace mon\http\Command;

use mon\env\Config;
use mon\http\Route;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 缓存路由表
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class RouteCacheCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'route:cache';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Cache the route.';

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
        $cache_route = Config::instance()->get('http.app.fpm.cache', '');
        if (!$cache_route) {
            return $out->block('Route cache file path error!', 'ERROR');
        }
        // 加载注册路由
        \support\http\Bootstrap::registerRoute();
        // 缓存路由信息
        $save = Route::instance()->cache($cache_route);
        if (!$save) {
            return $out->block('Build route cache error!', 'ERROR');
        }

        return $out->block('Build route cache success!', 'SUCCESS');
    }
}
