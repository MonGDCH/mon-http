<?php

declare(strict_types=1);

namespace mon\http;

use mon\util\File;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

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
    protected static $file_relation = [
        'process/Http.php'  => 'process/Http.php'
    ];

    /**
     * 移动的文件夹
     *
     * @var array
     */
    protected static $dir_relation = [
        'process/http'      => 'config/http'
    ];

    /**
     * 安装
     *
     * @return void
     */
    public static function install()
    {
        // 创建框架文件
        $source_path = __DIR__ . DIRECTORY_SEPARATOR;
        $desc_path = ROOT_PATH . DIRECTORY_SEPARATOR;
        // 移动文件
        foreach (static::$file_relation as $source => $desc) {
            $sourceFile = $source_path . $source;
            $descFile = $desc_path . $desc;
            File::instance()->copyFile($sourceFile, $descFile, true);
            echo "Create File $descFile\r\n";
        }
        // 移动目录
        foreach (static::$dir_relation as $source => $desc) {
            $sourceDir = $source_path . $source;
            $descDir = $desc_path . $desc;
            static::copydir($sourceDir, $descDir, true);
        }
    }

    /**
     * 卸载
     *
     * @return void
     */
    public static function uninstall()
    {
    }

    /**
     * 复制文件夹
     *
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     * @param boolean $overwrite   文件是否覆盖，默认不覆盖
     * @return void
     */
    protected static function copydir($source, $dest, $overwrite = false)
    {
        File::instance()->createDir($dest);
        echo "Create Dir $dest\r\n";
        $dir_iterator = new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
        /** @var RecursiveDirectoryIterator $iterator */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . '/' . $iterator->getSubPathName();
                File::instance()->createDir($sontDir);
                echo "Create Dir $sontDir\r\n";
            } else {
                $file = $dest . '/' . $iterator->getSubPathName();
                if (file_exists($file) && !$overwrite) {
                    continue;
                }

                copy($item, $file);
                echo "Create File $file\r\n";
            }
        }
    }
}
