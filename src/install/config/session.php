<?php

/*
|--------------------------------------------------------------------------
| Session配置文件
|--------------------------------------------------------------------------
| 定义Session配置
|
*/

return [
    // workerman session驱动
    'handler'               => \Workerman\Protocols\Http\Session\FileSessionHandler::class,
    // workerman session 驱动初始化传入参数
    'setting'               => [
        // 文件session保存路径
        'save_path' => RUNTIME_PATH . '/sess/',
    ],
    // 自动刷新session，默认：false
    'auto_update_timestamp' => true,
    // session名称，默认：PHPSID
    'session_name'          => 'PHPSID',
    // cookie有效期，默认：1440，cookie要比session有效期时间长一些
    'cookie_lifetime'       => 24 * 3600,
    // cookie路径，默认：/
    'cookie_path'           => '/',
    // 同站点cookie，默认：''
    'same_site'             => '',
    // cookie的domain，默认：''
    'domain'                => '',
    // 是否仅适用https的cookie，默认：false
    'secure'                => false,
    // session有效期，默认：1440
    'lifetime'              => 12 * 3600,
    // 是否开启http_only，默认：true
    'http_only'             => true,
    // gc的概率，默认：[1, 1000]
    'gc_probability'        => [1, 1000],
];
