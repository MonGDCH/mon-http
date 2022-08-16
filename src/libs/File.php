<?php

declare(strict_types=1);

namespace mon\http\libs;

use SplFileInfo;
use mon\http\exception\FileException;

/**
 * 文件对象
 * 
 * @see 修改自webman/File
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class File extends SplFileInfo
{
    /**
     * 移动文件
     *
     * @param string $destination   移动目标
     * @return File
     */
    public function move(string $destination): File
    {
        $error = '';
        set_error_handler(function ($type, $msg) use (&$error) {
            $error = $msg;
        });
        $path = pathinfo($destination, PATHINFO_DIRNAME);
        if (!is_dir($path) && !mkdir($path, 0777, true)) {
            restore_error_handler();
            throw new FileException(sprintf('Unable to create the "%s" directory (%s)', $path, strip_tags($error)));
        }
        if (!rename($this->getPathname(), $destination)) {
            restore_error_handler();
            throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $destination, strip_tags($error)));
        }
        restore_error_handler();
        @chmod($destination, 0666 & ~umask());
        return new self($destination);
    }
}
