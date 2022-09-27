## mon-http

支持`workerman`、`fpm`多种请求方式的http服务

### 实现功能

- 基于`workerman`的http服务器
- 基于`fast-route`的路由支持
- 中间件`Middleware`支持
- `Session`支持
- `fpm`访问支持
- 支持依赖注入

### 文档

[查看文档](/doc/Home.md)


### 使用

##### 直接作为HTTP服务器使用

请参考`example\workerman.php`文件实现


##### 作为`mongdch\gaia`框架的HTTP服务

1. 按需修改安装完成后创建的`process\Http`进程控制文件

2. 按需修改安装完成后创建的`config\http`目录下的配置文件

3. 在`process\Http`进程控制文件定义路由

4. 重启`Gaia`服务，访问定义的路由


更多请查看`example`目录相关demo