<?php

declare(strict_types=1);

namespace support\http\command;

use mon\util\File;
use mon\env\Config;
use mon\http\Route;
use mon\http\Router;
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
    protected static $defaultDescription = 'Cache the fpm route.';

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
        \support\http\Fpm::registerRoute();

        // 获取路由命名
        $routers = Router::getRouters();
        $routersData = var_export($routers, true);
        // 获取路由定义
        $routerData = Route::instance()->cache();
        // 保存路由缓存
        $cache = <<<Tmp
<?php
    return [
    'routers' => $routersData,
    'routerData' => $routerData,
];
Tmp;
        // 缓存路由文件
        $save = File::createFile($cache, $cache_route, false);
        if (!$save) {
            return $out->block('Build route cache error!', 'ERROR');
        }

        return $out->block('Build route cache success!', 'SUCCESS');
    }
}
