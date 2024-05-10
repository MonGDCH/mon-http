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
    'exception' => \support\http\HttpErrorHandler::class,
    // FPM服务配置
    'fpm'       => [
        // 是否启用fpm
        'enable'    => false,
        // 路由配置
        'route'     => [
            // 路由文件路径
            'path'      => ROOT_PATH . DIRECTORY_SEPARATOR . 'routes',
            // 是否递归加载子目录路由
            'recursive' => false,
            // 路由缓存文件
            'cache'     => RUNTIME_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'route_cache.php',
        ],
    ],
    // Gaia基于workerman的HTTP服务配置
    'workerman' => [
        // 是否启用进程
        'enable'    => false,
        // 进程配置
        'config'    => [
            // 监听协议端口
            'listen'    => 'http://0.0.0.0:8087',
            // 额外参数
            'context'   => [],
            // 通信协议
            'transport' => 'tcp',
            // 进程数
            'count'     => \gaia\App::cpuCount() * 2,
            // 进程用户
            'user'      => '',
            // 进程用户组
            'group'     => '',
            // 是否开启端口复用
            'reusePort' => false,
        ],
        // 是否每次业务重新创建控制器
        'newCtrl'   => true,
        // 路由配置
        'route'     => [
            // 路由文件路径
            'path'      => ROOT_PATH . DIRECTORY_SEPARATOR . 'routes',
            // 是否递归加载子目录路由
            'recursive' => false
        ],
        // 静态文件访问配置
        'static'    => [
            // 是否启用静态资源访问 
            'enable'    => false,
            // 静态资源目录
            'path'      => ROOT_PATH . '/public',
            // 允许访问的文件类型，空则不限制
            'ext_type'  => [],
        ]
    ]
];
