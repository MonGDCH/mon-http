# WorkerMan服务创建

### 构造方法

```php
__construct(bool $debug = true, bool $newCtrl = true, string $name = '__worker__')
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| handler | ExceptionHandlerInterface | 是 | 异常处理实例 |  |
| debug | boolean | 否 | 是否为调试模式 | true |
| newCtrl | boolean | 否 | 每次回调重新实例化控制器 | true |
| name | string | 否 | 应用名称，也是中间件名 | '__worker__' |


### 自定义错误处理类支持

```php
function supportError(string $error_class): WorkerMan
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| error_class | string | 是 | 实现 `ExceptionHandlerInterface` 接口的对象名称 |  |


### 请求类更换支持

```php
supportRequest(string $request_class): WorkerMan
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| request_class | string | 是 | 请求类名 |  |


### 静态文件支持

> 设置静态文件支持相关

```php
supportStaticFile(bool $supportSatic, string $staticPath, array $supportType = [], string $name = '__static__'): WorkerMan
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| supportSatic | boolean | 是  | 是否开启静态文件支持 |  |
| staticPath | string | 是  | 静态文件目录 |  |
| supportType | array | 否 | 支持的文件类型，空则表示所有 |  |
| name | string | 否 | 静态全局中间件名 | __static__ |


### Session扩展支持

> 设置Session扩展支持相关

```php
supportSession(array $config): WorkerMan
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| config | array | 是 | Session公共配置，必须存在 handler（驱动引擎，支持workerman内置驱动、或自定义驱动）、setting（驱动引擎构造方法传参） 两个参数 |  |


### 执行回调

```php
run(TcpConnection $connection, RequestInterface $request)
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| connection | TcpConnection | 是  | WorkerMan回调onMessage注入TcpConnection实例 |  |
| request | RequestInterface | 是 | WorkerMan回调onMessage注入绑定的请求实例  |  |


#### 清除回调处理器缓存

```php
clearCacheCallback(): App
```


### 获取运行模式

```php
debug(): bool
```


### 获取路由实例

```php
route(): Route
```



