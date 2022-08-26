<?php

declare(strict_types=1);

namespace mon\http;

/**
 * Gaia框架安装驱动
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Install
{
    /**
     * 标志为Gaia的驱动
     */
    const GAIA_PLUGIN = true;

    /**
     * 移动的文件
     *
     * @var array
     */
    protected static $file_relation = [];

    /**
     * 安装
     *
     * @return void
     */
    public static function install()
    {
    }

    /**
     * 卸载
     *
     * @return void
     */
    public static function uninstall()
    {
    }
}
