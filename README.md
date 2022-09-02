## mon-http

本项目为深度学习`webman`框架的基于`workerman`框架的衍生项目

### 实现功能

- 基于`workerman`的http服务器
- 路由`Route`快速定义支持
- 中间件`Middleware`支持
- Session支持

### 文档

[查看文档](/doc/Home.md)


### 使用

##### 直接作为HTTP服务器使用

请参考`example\example.php`文件实现


##### 作为`mongdch\gaia`框架的HTTP服务

1. 按需修改安装完成后创建的`process\Http`进程控制文件

2. 按需修改安装完成后创建的`config\http`目录下的配置文件

3. 在`process\Http`进程控制文件定义路由

4. 重启`Gaia`服务，访问定义的路由

