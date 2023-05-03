<?php

declare(strict_types=1);

namespace mon\http\Command;

use mon\util\File;
use mon\console\Input;
use mon\console\Output;
use mon\console\Command;

/**
 * 发布FPM入口文件
 *
 * @author Mon <98555883@qq.com>
 * @version 1.0.0
 */
class FpmCommand extends Command
{
    /**
     * 指令名
     *
     * @var string
     */
    protected static $defaultName = 'verdor:fpm';

    /**
     * 指令描述
     *
     * @var string
     */
    protected static $defaultDescription = 'Publish the fpm entry file.';

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
        $sourceFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'fpm.php';
        $destFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        File::instance()->copyFile($sourceFile, $destFile, true);
        return $out->block('Create File ' . $destFile, 'SUCCESS');
    }
}
