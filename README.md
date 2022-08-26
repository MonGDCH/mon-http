## mon-http

本项目为深度学习`webman`框架的基于`workerman`框架的衍生项目

### 实现功能

- 基于`workerman`的http服务器
- 路由`Route`快速定义支持
- 中间件`Middleware`支持
- Session支持


### 使用

1. 直接作为HTTP服务器使用

请参考`example\example.php`文件实现


2. 作为`mongdch\gaia`框架的HTTP服务

执行在`mongdch\gaia`框架下，使用`composer`安装，并修改`config\process.php`文件，增加如下配置

```php

'http'  => [
    'listen'        => 'http://0.0.0.0:8686',
    'transport'     => 'tcp',
    'context'       => [],
    'count'         =>  2,
    'user'          => '',
    'group'         => '',
    'reusePort'     => false,
    'handler'       => \mon\http\support\Http::class,
],

```

