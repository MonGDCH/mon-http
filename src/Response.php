<?php

declare(strict_types=1);

namespace mon\http;

use Throwable;
use Workerman\Protocols\Http\Response as HttpResponse;

/**
 * 响应处理
 * 
 * @see 修正自webman/Response
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Response extends HttpResponse
{
    /**
     * 异常实体
     *
     * @var Throwable
     */
    protected $_exception = null;

    /**
     * 输出文件流
     *
     * @param string $file 文件地址
     * @return Response
     */
    public function file(string $file): Response
    {
        if ($this->notModifiedSince($file)) {
            return $this->withStatus(304);
        }
        return $this->withFile($file);
    }

    /**
     * 下载保存文件
     *
     * @param string $file  文件地址
     * @param string $name  文件名
     * @return Response
     */
    public function download(string $file, string $name = ''): Response
    {
        $this->withFile($file);
        if ($name) {
            $this->header('Content-Disposition', "attachment; filename=\"$name\"");
        }
        return $this;
    }

    /**
     * 绑定异常
     *
     * @param Throwable|null $exception
     * @return Throwable
     */
    public function exception(Throwable $exception = null): Throwable
    {
        if ($exception) {
            $this->_exception = $exception;
        }
        return $this->_exception;
    }

    /**
     * 文件是否已修改
     *
     * @param string $file  文件地址
     * @return boolean
     */
    protected function notModifiedSince(string $file): bool
    {
        $if_modified_since = App::instance()->request()->header('if-modified-since');
        if ($if_modified_since === null || !($mtime = filemtime($file))) {
            return false;
        }
        return $if_modified_since === gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }
}
