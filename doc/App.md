# 应用核心驱动

### 初始化

> 初始化http服务

```php
init(Worker $worker, ExceptionHandler $handler, bool $debug = true, string $name = '__app__'): App
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| worker | Worker | 是 | Worker实例 |  |
| handler | ExceptionHandler | 是 | 异常处理实例 |  |
| debug | boolean | 否 | 是否为调试模式 | true |
| name | string | 否 | 应用名称，也是中间件名 | __app__ |


### 回调扩展支持

> 设置请求回调相关

```php
suppertCallback(bool $newController = true, string $request = Request::class, bool $scalar = true, int $maxCacheCallback = 1024): App
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| newController | boolean | 否  | 是否每次重新new控制器类 | true |
| request | string | 否  | HTTP请求响应的request类对象名 | Request::class |
| scalar | bool | 否 | 参数注入是否转换标量 | true |
| maxCacheCallback | integer | 否 | 最大缓存回调数，一般不需要修改 | 1024 |


### 静态文件支持

> 设置静态文件支持相关

```php
supportStaticFile(bool $supportSatic, string $staticPath, array $supportType = [], string $name = '__static__'): App
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
supportSession(string $handler, array $setting = [], array $config = []): App
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| handler | string | 是  | 驱动引擎，支持workerman内置驱动、或自定义驱动 |  |
| setting | array | 否 | 驱动引擎构造方法传参 |  |
| config | array | 否 | Session公共配置 |  |



### 绑定路由实例

> 绑定应用响应路由实例

```php
bindRoute(Route $route): App
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| route | Route | 是  | 路由对象实例 |  |



#### 清除回调处理器缓存

```php
clearCacheCallback(): App
```


### 获取运行模式

```php
debug(): bool
```


### 获取woker实例

```php
worker(): Worker
```


### 获取TCP链接实例

```php
connection(): TcpConnection
```


### 获取请求实例

```php
request(): Request
```


### 获取路由实例

```php
route(): Request
```









