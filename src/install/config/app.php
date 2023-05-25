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
    // 是否每次业务重新创建控制器
    'newCtrl'   => true,
    // 路由文件路径
    'routePath' => ROOT_PATH . DIRECTORY_SEPARATOR . 'routes',
    // 是否递归加载子目录路由
    'recursive' => false
];
