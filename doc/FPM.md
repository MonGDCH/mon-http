# FPM服务创建

### 构造方法

```php
__construct(bool $debug = true, string $name = '__fpm__')
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| debug | boolean | 否 | 是否为调试模式 | true |
| name | string | 否 | 应用名称，也是中间件名 | '__fpm__' |


### 自定义错误处理类支持

```php
function supportError(string $error_class): Fpm
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| error_class | string | 是 | 实现 `ExceptionHandlerInterface` 接口的对象名称 |  |



### Session扩展支持

> 设置Session扩展支持相关

```php
supportSession(array $config = []): Fpm
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| config | array | 否 | Session公共配置 |  |


### 执行回调

```php
run(bool $exit = true)
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| exit | bool | 是  | 是否exit结束程序 | true |



### 获取运行模式

```php
debug(): bool
```


### 获取路由实例

```php
route(): Route
```










