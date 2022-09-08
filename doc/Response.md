# 响应处理器

### 输出文件流

```php
file(string $file): Response
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| file | string | 是  | 文件路径 |  |


### 下载保存文件

```php
download(string $file, string $name = ''): Response
```

#### 参数说明

| 参数名 | 类型 | 是否必须 | 描述 | 默认值 |
| ------------ | ------------ | ------------ | ------------ | ------------ |
| file | string | 是  | 文件路径 |  |
| 文件名 | string | 否   | 文件路径 | 文件路径名称  |