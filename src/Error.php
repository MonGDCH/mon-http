<?php

declare(strict_types=1);

namespace mon\worker;

use ErrorException;
use mon\util\Instance;

/**
 * 异常错误处理
 *
 * @author  Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Error
{
    use Instance;

    /**
     * 是否为调试模式
     *
     * @var boolean
     */
    protected $debug = true;

    /**
     * 私有化初始化
     */
    protected function __construct()
    {
    }

    /**
     * 注册初始化
     *
     * @param boolean $debug 是否调试模式
     * @return void
     */
    public function register(bool $debug = true): void
    {
        $this->debug = $debug;
        // 是否显示错误
        ini_set('display_errors', $debug ? 'on' : 'off');
        // 报告所有错误级别
        error_reporting(E_ALL);
        // 错误
        set_error_handler([$this, 'appError']);
        // 致命错误|结束运行
        register_shutdown_function([$this, 'fatalError'], time());
    }

    /**
     * 应用错误
     *
     * @param integer $level    错误编号
     * @param string $message   详细错误信息
     * @param string $file      出错的文件
     * @param integer $line     出错行号
     * @throws ErrorException
     * @return void
     */
    public function appError(int $level, string $message, string $file = '', int $line = 0): void
    {
        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * 致命异常，PHP结束运行
     *
     * @param integer $time 程序开始运行时间
     * @return void
     */
    public function fatalError(int $time): void
    {
        if (time() - $time <= 1) {
            sleep(1);
        }
    }
}
