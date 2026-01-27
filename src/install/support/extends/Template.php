<?php

declare(strict_types=1);

namespace support\http\extends;

use mon\util\View;
use mon\http\Response;

/**
 * 视图扩展
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
trait Template
{
    /**
     * 视图实例
     *
     * @var View
     */
    protected $view = null;

    /**
     * 视图路径
     *
     * @var string
     */
    protected $view_path = APP_PATH . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

    /**
     * 获取视图实例
     * 基础实例可重载该方法，实现自定义视图引擎
     *
     * @return View
     */
    protected function getView(): View
    {
        if (is_null($this->view)) {
            $this->view = new View();
            $this->view->setPath($this->view_path);
        }

        return $this->view;
    }

    /**
     * 设置视图变量
     *
     * @param mixed $key
     * @param mixed $value
     * @return Controller
     */
    protected function assign($key, $value = null): static
    {
        $this->getView()->assign($key, $value);
        return $this;
    }

    /**
     * 输出视图
     *
     * @param string $view
     * @param array $data
     * @return Response
     */
    protected function fetch(string $view, array $data = []): Response
    {
        $view = $this->getView()->fetch($view, $data);
        $this->view = null;
        return $this->text($view);
    }

    /**
     * 返回视图内容(不补全视图路径)
     *
     * @param string $view  完整的视图路径
     * @param array $data   视图数据
     * @return Response
     */
    protected function display(string $view, array $data = []): Response
    {
        $view = $this->getView()->display($view, $data);
        $this->view = null;
        return $this->text($view);
    }
}
