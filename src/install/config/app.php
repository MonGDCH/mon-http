<?php

/*
|--------------------------------------------------------------------------
| HTTP服务配置文件
|--------------------------------------------------------------------------
| 定义HTTP服务配置
|
*/

return [
    // 异常错误处理器
    'exception' => \support\http\ErrorHandler::class,
    // FPM服务配置
    'fpm'       => [
        // 是否启用fpm
        'enable'    => env('HTTP_FPM_ENABLE', false),
        // 路由缓存文件
        'cache'     => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'fpm_route_cache.php',
    ],
    // Gaia基于workerman的HTTP服务配置
    'workerman' => [
        // 进程配置
        'config'    => [
            // 监听协议端口
            'listen'    => 'http://' . env('HTTP_LISTEN_HOST', '0.0.0.0') . ':' . env('HTTP_LISTEN_PORT', 8087),
            // 额外参数
            'context'   => [],
            // 通信协议
            'transport' => 'tcp',
            // 进程数，默认0表示自动根据CPU核心数x2计算
            'count'     => env('HTTP_WORKER_COUNT', 0),
            // 进程用户
            'user'      => '',
            // 进程用户组
            'group'     => '',
            // 是否开启端口复用
            'reusePort' => false,
        ],
        // 是否每次业务重新创建控制器
        'newCtrl'   => true,
        // 静态文件访问配置
        'static'    => [
            // 是否启用静态资源访问 
            'enable'    => env('HTTP_STATIC_ENABLE', false),
            // 静态资源目录
            'path'      => ROOT_PATH . '/public',
            // 允许访问的文件类型，空则不限制
            'ext_type'  => [],
        ]
    ]
];
