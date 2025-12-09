<?php

declare(strict_types=1);

namespace support\command\http;

use gaia\App;
use gaia\Gaia;
use support\Plugin;
use support\http\Http;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * app 指令
 *
 * Class App
 * @author Mon <985558837@qq.com>
 * @copyright Gaia
 * @version 1.0.0 2025-12-08
 */
class HttpAppCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'http:app';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Start the http app service.';

    /**
     * 指令分组
     *
     * @var string
     */
    protected static $defaultGroup = 'mon-http';

    /**
     * 应用名称
     *
     * @var string
     */
    protected $name = 'http-app';

    /**
     * 启动进程
     *
     * @example 进程名 => 进程驱动类名, eg: ['test' => Test::class]
     * @var array
     */
    protected $process = [
        'http' => Http::class
    ];

    /**
     * 开启插件支持
     *
     * @var boolean
     */
    protected $supportPlugin = true;

    /**
     * 执行指令的接口方法
     *
     * @param Input $input		输入实例
     * @param Output $output	输出实例
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        if (empty($this->process)) {
            echo '未定义启动进程';
            return;
        }
        if (empty($this->name)) {
            echo '未定义应用名称';
            return;
        }

        // 初始化
        App::init($this->name);

        // 加载插件
        $this->supportPlugin && Plugin::register();

        // TODO 更多操作

        // 启动服务
        Gaia::instance()->runProcess($this->process);
    }
}
