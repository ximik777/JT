<?php

namespace JT\Core;

use JT\Core\Router\RouteParam;

abstract class ViewDefault
{
    var $content = '';

    var $template_path = null;
    var $view_path = null;

    var $data = array();
    var $session = array();

    function __construct(RouteParam $param, $data = array())
    {
        $this->template_path = $param->template_path;
        $this->view_path = $param->view_path . '/' . $param->view_name;
        $this->data = $data;
    }

    private function getRealPath($path)
    {
        if (is_file($path = (strncmp($path, '/', 1) === 0 ? '' : DR . '/') . $path . '.php')) {
            return $path;
        }
        return false;
    }

    function __toString()
    {
        $this->view_path = $this->view_path ? $this->getRealPath($this->view_path) : null;
        $this->template_path = $this->template_path ? $this->getRealPath($this->template_path) : null;

        if (!$this->view_path) {
            if ($this->view_path === false) {
                $this->content = "View not found";
            }
        } else {
            ob_start();
            include $this->view_path;
            $this->content = ob_get_contents();
            ob_end_clean();
        }

        if (!$this->template_path) {
            if ($this->template_path === false) {
                $this->content = "Template not found";
            }
        } else {
            ob_start();
            include $this->template_path;
            $this->content = ob_get_contents();
            ob_end_clean();
        }

        return $this->content;
    }
}

