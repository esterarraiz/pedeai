<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = [];

    public function __construct()
    {
        #As rotas serão definidas aqui
    }

    public function add($route, $params = [])
    {
        
        $route = str_replace('{id}', '(\d+)', $route);
       
        $route = preg_replace('/\//', '\\/', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    public function match($url)
    {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                $this->params = $params;
                
                if (isset($matches[1])) {
                    $this->params['id'] = $matches[1];
                }
                return true;
            }
        }
        return false;
    }

    public function dispatch()
    {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $url = $this->removeQueryStringVariables($url);

        if ($this->match($url)) {
            $controller = "App\\Controllers\\" . $this->params['controller'];

            if (class_exists($controller)) {
                $controller_object = new $controller();
                $action = $this->params['action'];

                if (method_exists($controller_object, $action)) {
                    
                    if (isset($this->params['id'])) {
                        $controller_object->$action($this->params['id']);
                    } else {
                        $controller_object->$action();
                    }
                } else {
                    echo "Action '$action' não encontrada no controller '$controller'";
                }
            } else {
                echo "Controller '$controller' não encontrado.";
            }
        } else {
            echo "Página não encontrada (Erro 404) para a URL: " . htmlspecialchars($url);
        }
    }

    protected function removeQueryStringVariables($url)
    {
        if ($url != '') {
            $parts = explode('?', $url, 2);
            $url = $parts[0];
        }
        return $url;
    }
}
