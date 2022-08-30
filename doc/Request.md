# 请求处理器


### 获取控制器

```php
controller(): string
```

### 获取控制器回调方法

```php
action(): string
```


### 生成URL

```php
build(string $url = '', array $vars = []): string
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| url | string | 否  | URL路径 |  |
| vars | array | 否 | 传参 |  |


### 获取GET数据

```php
get($name = null, $default = null, $filter = true): mixed
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| name | string|null | 否  | 参数键名 |  |
| default | array | 否 | 默认值 |  |
| filter | bool | 否 | 是否过滤参数 |  |


### 获取POST数据

```php
post($name = null, $default = null, $filter = true): mixed
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| name | string|null | 否  | 参数键名 |  |
| default | array | 否 | 默认值 |  |
| filter | bool | 否 | 是否过滤参数 |  |



### 获取application/json参数

```php
json($name = null, $default = null, $filter = true): mixed
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| name | string|null | 否  | 参数键名 |  |
| default | array | 否 | 默认值 |  |
| filter | bool | 否 | 是否过滤参数 |  |



### 获取上传文件

```php
file($name = null): null|UploadFile[]|UploadFile
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| name | string|null | 否  | 文件名 |  |


更多请查看`Request`类源码