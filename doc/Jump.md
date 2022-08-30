# 业务中转支持


### 页面跳转

```php
redirect(string $url = '', array $vars = [], int $code = 302, array $header = [])
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| url | string | 否  | 跳转URL |  |
| vars | array | 否 | URl参数 |  |
| code | integer | 否 | 跳转状态码 | 302 |
| header | array | 否 | 响应头 |  |


### 程序结束

```php
abort(int $code, string $msg = null, array $header = [])
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| code | integer | 是 | 状态码 |  |
| msg | string | 否 | 返回内容 |  |
| header | array | 否 | 响应头 |  |



### 响应结果集

```php
result(int $code = 0, string $msg = '', array $data = [], array $extend = [], string $type = 'json', int $http_code = 200, array $header = [])
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| code | integer | 否 | 数据集code值 |  |
| msg | string | 否 | 数据集提示信息 |  |
| data | array | 否 | 数据集结果集 |  |
| extend | array | 否 | 扩展数据集数据 |  |
| type | string | 否 | 返回数据类型，默认Json，支持json、xml类型 |  |
| http_code | integer | 是 | 响应状态码 | 200 |
| header | array | 否 | 响应头 |  |


