<?php


namespace JT\Core\Router;

use JT\Core\Config;
use JT\Core\ControllerDefault;

class Route
{
    private $bundle_name = '';
    private $view_name = '';
    private $controller_name = '';
    private $action_name = '';

    private $result = '';

    private $config = array(
        'tree' => array(
            'bundle' => 'bundle',
            'controller' => 'controller'
        ),
        'default' => array(
            'bundle' => 'main',
            'controller' => 'index',
            'action' => 'index',
            'template' => 'template/default'
        ),
        'prefix' => array(
            'view' => 'View_',
            'controller' => 'Controller_',
            'action' => 'Action_'
        ),
        'routes' => array(
            '/this/is/an/example/of/routing/' => 'main:index'
        ),
        'split_controller' => array(// bundle => [controller, controller, ...]
        )
    );

    public function __construct($request_uri = false, $config = null, $request = array())
    {
        if (is_string($config)) {
            $config = Config::get($config, []);
        }

        if (!is_array($config)) {
            $config = [];
        }

        $this->config = array_replace_recursive($this->config, $config);

        if (count($request)) {
            $_REQUEST = array_merge($_REQUEST, $request);
        }

        $this->routesPrepare();

        $param = new RouteParam();

        $param->bundle_name = $this->config['default']['bundle'];
        $param->controller_name = $this->config['default']['controller'];
        $param->action_name = $this->config['default']['action'];

        $request_uri = trim($request_uri, '/\\');
        
        if (($pos = strpos($request_uri, '?')) !== false) {
            $request_uri = substr($request_uri, 0, $pos);
        }

        if (empty($request_uri)) {
            $request_uri = $param->controller_name;
        }

        $path = explode("/", strtr($request_uri, '.', '/'));

        if (is_array($this->config['routes']) && isset($this->config['routes'][$request_uri])) {
            $path = $this->config['routes'][$request_uri];
        }

        if (isset($path[0]) && is_dir(DR . '/bundle/' . strtolower($path[0]))) {
            $param->bundle_name = array_shift($path);
        }

        if (isset($path[0])) {
            $param->controller_name = array_shift($path);
        }

        if (isset($path[0]) && isset($this->config['split_controller'][$param->bundle_name]) && in_array($param->controller_name, $this->config['split_controller'][$param->bundle_name])) {
            $param->controller_name .= '_' . array_shift($path);
        }

        if (isset($path[0])) {
            $param->action_name = implode('_', $path);
        }

        $param->view_name = $param->action_name == $this->config['default']['action'] ? $this->config['prefix']['view'] . $param->controller_name : $this->config['prefix']['view'] . $param->controller_name . '_' . $param->action_name;
        $param->controller = $param->controller_name;
        $param->controller_name = $this->config['prefix']['controller'] . $param->controller_name;
        $param->view_path = "view/{$param->bundle_name}/{$param->view_name}.twig";
        $param->action_name = $this->config['prefix']['action'] . $param->action_name;
        $param->bundle_path = "bundle\\{$param->bundle_name}";
        $param->template_path = $this->config['default']['template'];
        $param->controller_path = $param->bundle_path . '\\' . ($this->config['tree']['controller'] ? $this->config['tree']['controller'].'\\': '') . $param->controller_name;

        if (class_exists("bundle\\General")) {
            call_user_func(["bundle\\General", "load"], $param);
        }

        if (class_exists($param->controller_path)) {
            $this->result = new $param->controller_path($param);
        } else {
            $controller = $param->bundle_path. '\\' . ($this->config['tree']['controller'] ? $this->config['tree']['controller'].'\\': '') . "Controller";
            if (class_exists($controller)) {
                $this->result = new $controller($param);
            } else {
                header("HTTP/1.0 404 Not Found");
                $param->view_path = "{$param->template_path}\\view\\View_404";
                $this->result = new ControllerDefault($param);
            }
        }
    }

    private function __clone()
    {
    }

    public function routesPrepare()
    {
        if (!is_array($this->config['routes'])) {
            $this->config['routes'] = array();
        }

        foreach ($this->config['routes'] as $k => $v) {
            $this->config['routes'][(trim($k, '/\\'))] = explode(':', $v);
        }
    }

    function __toString()
    {
        return strval($this->result);
    }
}
