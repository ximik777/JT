<?php

namespace JT\Core;

use JT\Core\Router\RouteParam;
use Twig_Autoloader;
use Twig_Environment;
use Twig_Loader_Filesystem;

class ControllerDefault
{
    protected $param = null;

    protected $response = array(
        'error' => false,
        'message' => ''
    );

    protected $result = null;

    public function bundleInit()
    {
        return true;
    }

    public function beforeInit()
    {
        return true;
    }

    public function missingInit()
    {
        if (Server::is_ajax()) {
            $this->setFieldError('page-not-found');
            return null;
        }

        return $this->render('view/main/404.twig');
    }

    public function afterInit()
    {
    }

    public function shutdown()
    {
    }

    public function is_render()
    {
    }

    function __construct(RouteParam $param)
    {
        $this->param = $param;

        register_shutdown_function([
            $this, 'shutdown'
        ]);

        if ($this->bundleInit() === false) {
            return;
        }

        if (!method_exists($this, $this->param->action_name)) {
            $this->result = $this->missingInit();
            return;
        }

        if ($this->beforeInit() === false) {
            return;
        }

        $this->result = call_user_func([$this, $this->param->action_name]);

        $this->afterInit();
    }

    protected function setResponse($key, $value)
    {
        $this->response[$key] = $value;
    }

    protected function setFieldError($message = "")
    {
        $this->response['error'] = true;
        $this->response['message'] = $message;
    }

    protected function setTemplatePath($template_path)
    {
        $this->param->template_path = $template_path;
    }

    protected function setViewPath($view_path)
    {
        $this->param->view_path = $view_path;
    }

    protected function setViewName($view_name)
    {
        $this->param->view_name = $view_name;
    }

    public function __render($view_path = null, $template_path = null, $data = array())
    {
        try{
            $twig = new Twig_Environment(
                new Twig_Loader_Filesystem([$template_path])
            );
        } catch (\Exception $e){
            return "Template not found";
        }

        try{
            return $twig->render($view_path, $data);
        } catch (\Exception $e){
            try {
                return $twig->render('view/main/404.twig', $data);
            } catch (\Exception $e) {
                return 'View not found';
            }
        }
    }

    protected function render($view_path = null, $template_path = null)
    {
        $this->is_render();

        if ($view_path)
            $this->setViewPath($view_path);

        if ($template_path)
            $this->setTemplatePath($template_path);

        return $this->__render($this->param->view_path, $this->param->template_path, $this->response);
    }

    function __toString()
    {
        if (is_string($this->result)) {
            header("Content-Type: text/html; charset=utf-8");
            return $this->result;
        }

        if (is_array($this->result)) {
            header("Content-Type: text/javascript; charset=utf-8");
            return (string)new Json($this->result);
        }

        header("Content-Type: text/javascript; charset=utf-8");
        return (string)new Json($this->response, JSON_UNESCAPED_UNICODE);
    }
}