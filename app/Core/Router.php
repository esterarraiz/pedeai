<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = [];

    public function __construct()
    {
        $this->add('login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('logout', ['controller' => 'AuthController', 'action' => 'logout']);

        # Rotas dos Dashboards (Protegida)
        $this->add('dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index']);
        $this->add('dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index']);
        $this->add('dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index']);
        $this->add('dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index']);
        $this->add('dashboard/generico', ['controller' => 'GenericDashboardController', 'action' => 'index']);
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
        $url = trim($url, '/'); // Importante: remover barras no início/fim
        $url = $this->removeQueryStringVariables($url);

        if ($this->match($url)) {
            // Namespace completo do Controller
            $controller = "App\\Controllers\\" . $this->params['controller'];

            if (class_exists($controller)) {
                // Passa os parâmetros da rota (ex: 'id') para o construtor do Controller Base
                $controller_object = new $controller($this->params); 
                
                $action = $this->params['action'];

                if (is_callable([$controller_object, $action])) {
                    // Substituí a lógica anterior pela chamada __call do Controller Base
                    // Isso nos permite rodar os filtros 'before' e 'after'
                    $controller_object->$action($this->params);

                } else {
                    echo "Action '$action' não encontrada ou não é 'callable' no controller '$controller'";
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
