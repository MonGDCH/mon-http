<?php

namespace mon\worker\interfaces;

use mon\worker\App;

/**
 * 中间件接口
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
interface Middleware
{
    /**
     * 中间件回调
     *
     * @param App $app  App实例
     * @param array $vals   路由参数
     * @return mixed
     */
    public function handler(App $app, array $vals = []);
}
