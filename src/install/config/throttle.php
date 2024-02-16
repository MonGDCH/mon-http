<?php
/*
|--------------------------------------------------------------------------
| 节流设置配置文件
|--------------------------------------------------------------------------
| 定义节流设置配置
|
*/

return [
    // 是否启用节流请求
    'enable'    => false,

    // Psr-16通用缓存库规范: https://blog.csdn.net/maquealone/article/details/79651111
    // Cache驱动必须符合PSR-16缓存库规范，最低实现get/set俩个方法
    'cache_name'                    => \mon\cache\Cache::class,

    /*
     * 设置节流算法，组件提供了四种算法：
     *  - CounterFixed : 计数固定窗口算法
     *  - CounterSlider: 滑动窗口算法
     *  - TokenBucket  : 令牌桶算法
     *  - LeakyBucket  : 漏桶限流算法
     */
    'driver_name'                   => \support\http\middleware\throttle\CounterFixed::class,

    // 缓存键前缀，防止键值与其他应用冲突
    'prefix'                        => 'throttle_',

    // 要被限制的请求类型, eg: GET POST PUT DELETE HEAD
    'visit_method'                  => ['GET'],

    // 设置访问频率，例如 '10/m' 指的是允许每分钟请求10次。值 null 表示不限制,
    // eg: null 10/m  20/h  300/d 200/300
    'visit_rate'                    => '100/m',

    // 响应体中设置速率限制的头部信息，含义见：https://docs.github.com/en/rest/overview/resources-in-the-rest-api#rate-limiting
    'visit_enable_show_rate_limit'  => true,
];
