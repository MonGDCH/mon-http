<?php

/*
|--------------------------------------------------------------------------
| 初始化支持文件
|--------------------------------------------------------------------------
*/

if (!function_exists('dump')) {
    /**
     * 浏览器打印调试变量
     *
     * @param mixed ...$args    调试打印的值
     * @throws \mon\http\exception\DumperException
     * @return void
     */
    function dump(...$args): void
    {
        throw new \mon\http\exception\DumperException($args);
    }
}

if (!function_exists('cpu_count')) {
    /**
     * 获取服务器CPU内核数
     *
     * @return integer
     */
    function cpu_count(): int
    {
        // Windows 不支持进程数设置
        if (DIRECTORY_SEPARATOR === '\\') {
            return 1;
        }
        $count = 4;
        if (is_callable('shell_exec')) {
            if (strtolower(PHP_OS) === 'darwin') {
                $count = (int)shell_exec('sysctl -n machdep.cpu.core_count');
            } else {
                $count = (int)shell_exec('nproc');
            }
        }
        return $count > 0 ? $count : 4;
    }
}

// Gaia环境，进行指令注册
if (class_exists(\gaia\App::class)) {
    $path = __DIR__ . '/command';
    $namespance = 'mon\\http\\command';
    \gaia\App::console()->load($path, $namespance);
}
