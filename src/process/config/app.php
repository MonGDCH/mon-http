<?php

return [
    // 异常错误处理器
    'exception' => \support\http\HttpErrorHandler::class,
    // 是否每次业务重新创建控制器
    'newCtrl'   => true,
    // 防火墙中间件
    'firewall'  => [
        // IP黑名单
        'black' => [],
        // IP白名单
        'white' => []
    ]
];
