<?php

// workerman模式下，静态文件访问支持
return [
    // 是否启用静态资源访问 
    'enable'    => false,
    // 静态资源目录
    'path'      => ROOT_PATH . '/public',
    // 允许访问的文件类型，空则不限制
    'ext_type'  => [],
];
