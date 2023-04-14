<?php

declare(strict_types=1);

namespace mon\http;

use Throwable;
use mon\http\interfaces\RequestInterface;

/**
 * 响应处理
 * 
 * @see 修正自webman/Response
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Response extends \Workerman\Protocols\Http\Response
{
    /**
     * 异常实体
     *
     * @var Throwable
     */
    protected $_exception = null;

    /**
     * 重载构造方法
     *
     * @param integer $status   状态码
     * @param array $headers    请求头
     * @param string $body      响应内容
     */
    public function __construct(int $status = 200, array $headers = [], string $body = '')
    {
        parent::__construct($status, $headers, $body);
        $this->header('Server', 'Gaia HTTP');
    }

    /**
     * 输出文件流
     *
     * @param string $file 文件地址
     * @param RequestInterface $request 请求实例或者null
     * @return Response
     */
    public function file(string $file, RequestInterface $request = null): Response
    {
        if ($request && $this->notModifiedSince($file, $request)) {
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
        if ($name) {
            $this->header('Content-Disposition', "attachment; filename=\"$name\"");
        }

        return $this->withFile($file);
    }

    /**
     * 绑定异常
     *
     * @param Throwable|null $exception
     * @return Throwable
     */
    public function exception(Throwable $exception = null): ?Throwable
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
     * @param RequestInterface $request 请求实例
     * @return boolean
     */
    protected function notModifiedSince(string $file, RequestInterface $request): bool
    {
        $if_modified_since = $request->header('if-modified-since');
        if ($if_modified_since === null || !($mtime = filemtime($file))) {
            return false;
        }

        return $if_modified_since === gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }
}
