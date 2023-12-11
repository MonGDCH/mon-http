## mon-http

支持`workerman`、`fpm`多种请求方式的http服务

### 实现功能

- 基于`workerman`的http服务器
- 基于`fast-route`的路由支持
- 中间件`Middleware`支持，内置`Cors`跨域、`Firewall`防火墙、`Logger`日志、`Throttle`限流等中间件
- `Session`支持
- `fpm`访问支持
- 依赖注入支持

### 文档

[查看文档](/doc/Home.md)


### 使用

##### 直接作为HTTP服务器使用

请参考`example\workerman.php`文件实现


##### 作为`mongdch\gaia`框架的HTTP服务

1. `composer`安装完成后，执行`gaia vendor:publish mon\http`发布组件代码

2. 按需修改安装完成后创建的`process\Http`进程控制文件

3. 按需修改安装完成后创建的`config\http`目录下的配置文件

4. 在`process\Http`进程控制文件定义路由

5. 重启`Gaia`服务，访问定义的路由


更多请查看`example`目录相关demo