# 中间件支持

中间件需要实现`mon\http\interfaces`接口

```php

use mon\http\interfaces\Middleware;

class MiddlewareA implements Middleware
{
    public function process(Request $request, Closure $callback): Response
    {
        // 执行前置逻辑...

        $response = $callback($request);

        // 还可以执行后置逻辑...

        return $response;
    }
}


```


定义路由回调中间件使用请参考 [路由处理器](./Route.md)