<?php

declare(strict_types=1);

namespace mon\worker\libs;

/**
 * 文件上传文件对象
 * 
 * @see 修正自webman/UploadFile
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UploadFile extends File
{
    /**
     * 上传文件名
     *
     * @var string
     */
    protected $_uploadName = null;

    /**
     * 上传文件mime-type
     *
     * @var string
     */
    protected $_uploadMimeType = null;

    /**
     * 上传文件错误码
     *
     * @var integer
     */
    protected $_uploadErrorCode = null;

    /**
     * 重载SplFileInfo构造方法
     *
     * @param string $file_name
     * @param string $upload_name
     * @param string $upload_mime_type
     * @param integer $upload_error_code
     */
    public function __construct(string $file_name, string $upload_name, string $upload_mime_type, int $upload_error_code)
    {
        $this->_uploadName = $upload_name;
        $this->_uploadMimeType = $upload_mime_type;
        $this->_uploadErrorCode = $upload_error_code;
        parent::__construct($file_name);
    }

    /**
     * 获取上传文件名
     *
     * @return string
     */
    public function getUploadName(): string
    {
        return $this->_uploadName;
    }

    /**
     * 获取上传文件mime-type
     *
     * @return string
     */
    public function getUploadMineType(): string
    {
        return $this->_uploadMimeType;
    }

    /**
     * 获取上传文件扩展名
     *
     * @return string
     */
    public function getUploadExtension(): string
    {
        return pathinfo($this->_uploadName, PATHINFO_EXTENSION);
    }

    /**
     * 获取上传文件错误码
     *
     * @return integer
     */
    public function getUploadErrorCode(): int
    {
        return $this->_uploadErrorCode;
    }

    /**
     * 验证文件上传结果
     *
     * @return boolean
     */
    public function isValid(): bool
    {
        return $this->_uploadErrorCode === UPLOAD_ERR_OK;
    }
}
