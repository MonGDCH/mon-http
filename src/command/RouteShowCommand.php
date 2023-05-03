<?php

declare(strict_types=1);

namespace mon\http\Command;

use mon\http\Route;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 查看路由表
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class RouteShowCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'route:show';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Displays the defined route table.';

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
        $route = new Route();
        \support\http\Bootstrap::registerRoute($route);
        // 生成表格
        $columns = ['method', 'path', 'callback', 'middleware'];
        $data = $route->getData();
        $res = [];
        foreach ($data[0] as $method => $item) {
            foreach ($item as $path => $info) {
                $res[] = [
                    'method'    => $method,
                    'path'      => $path,
                    'callback'  => $this->getCallback($info['callback']),
                    'middleware' => isset($info['middleware']) ? implode(', ', $info['middleware']) : '',
                ];
            }
        }

        return $out->table($res, 'Router Table', $columns);
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
