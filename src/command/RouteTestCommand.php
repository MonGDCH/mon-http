<?php

declare(strict_types=1);

namespace mon\http\Command;

use mon\http\Route;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;
use FastRoute\Dispatcher;

/**
 * 测试请求路径
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class RouteTestCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'route:test';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Test the pathinfo is valid. Use route:test [method] url.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'mon-http';

    /**
     * 执行指令
     *
     * @param  Input  $in  输入实例
     * @param  Output $out 输出实例
     * @return integer  exit状态码
     */
    public function execute(Input $in, Output $out)
    {
        // 加载注册路由
        \support\http\Bootstrap::registerRoute();

        $args = $in->getArgs();
        $method = 'GET';
        if (isset($args[0]) && isset($args[1])) {
            $method = strtoupper($args[0]);
            $path = $args[1];
        } else if (isset($args[0])) {
            $path = $args[0];
        } else {
            return $out->block('please input test uri pathinfo', 'ERROR');
        }
        $columns = ['method', 'path', 'callback', 'middleware'];
        $callback = Route::instance()->dispatch($method, $path);
        if ($callback[0] == Dispatcher::FOUND) {
            $info = $callback[1];
            $table = [];
            $table[] = [
                'method'    => $method,
                'path'      => $path,
                'callback'  => $this->getCallback($info['callback']),
                'middleware' => isset($info['middleware']) ? implode(', ', $info['middleware']) : '',
            ];
            return $out->table($table, 'Callback Table', $columns);
        }

        return $out->error('[error] Route is not found');
    }

    /**
     * 获取回调名称
     *
     * @param mixed $callback 回调
     * @return string
     */
    protected function getCallback($callback): string
    {
        if (is_string($callback)) {
            return $callback;
        }

        if (is_array($callback)) {
            $ctrl = $callback[0];
            $action = $callback[1];
            if (is_object($ctrl)) {
                $ctrl = get_class($ctrl);
            }

            return $ctrl . '@' . $action;
        }

        return '- Closure Function';
    }
}
