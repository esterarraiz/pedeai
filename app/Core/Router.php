<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = [];

    public function __construct()
    {
        // === ROTAS DE AUTENTICAÇÃO ===
        $this->add('GET', 'login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('POST', 'login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('GET', 'logout', ['controller' => 'AuthController', 'action' => 'logout']);
        
        // === ROTAS DE DASHBOARDS ===
        // ROTA ADICIONADA: Redireciona /dashboard para /login
        $this->add('GET', 'dashboard', ['controller' => 'AuthController', 'action' => 'redirectToLogin']);
        
        $this->add('GET', 'dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index']);
        $this->add('GET', 'dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index']);
        $this->add('GET', 'dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index']);
        $this->add('GET', 'dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index']);
        
        // === ROTA PARA A AÇÃO DA COZINHA ===
        $this->add('POST', 'cozinha/pedido/pronto', ['controller' => 'CozinheiroDashboardController', 'action' => 'marcarPronto']);
        
        $this->add('GET', 'dashboard/generico', ['controller' => 'GenericDashboardController', 'action' => 'index']);

        // === ROTAS DE PEDIDOS ===
        $this->add('GET', 'pedidos/novo/{id:\d+}', ['controller' => 'PedidoController', 'action' => 'showFormNovoPedido']);
        $this->add('POST', 'pedidos/criar', ['controller' => 'PedidoController', 'action' => 'criarPedido']);
        $this->add('POST', 'pedidos/processar-ajax', ['controller' => 'PedidoController', 'action' => 'processarPedidoAjax']);
        $this->add('GET', 'pedidos/prontos', ['controller' => 'PedidoController', 'action' => 'buscarPedidosProntos']);

        // === ROTAS DE MESAS ===
        $this->add('GET', 'mesas', ['controller' => 'MesaController', 'action' => 'index']);
        $this->add('POST', 'mesas/liberar', ['controller' => 'MesaController', 'action' => 'liberarMesa']);
        $this->add('GET', 'mesas/detalhes/{id:\d+}', ['controller' => 'MesaController', 'action' => 'showDetalhesMesa']);
    }

    // O resto do arquivo continua igual...
    
    public function add($method, $route, $params = []) {
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . str_replace('/', '\/', $route) . '$/i';
        $this->routes[] = [ 'method' => strtoupper($method), 'route'  => $route, 'params' => $params ];
    }
    public function match($url) {
        $current_method = $_SERVER['REQUEST_METHOD'];
        foreach ($this->routes as $routeInfo) {
            if ($routeInfo['method'] === $current_method && preg_match($routeInfo['route'], $url, $matches)) {
                $this->params = $routeInfo['params'];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) { $this->params[$key] = $value; }
                }
                return true;
            }
        }
        return false;
    }
    public function dispatch() {
        $url = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if ($this->match($url)) {
            $controller = "App\\Controllers\\" . $this->params['controller'];
            if (class_exists($controller)) {
                $controller_object = new $controller(); 
                $action = $this->params['action'];
                if (method_exists($controller_object, $action)) {
                    $controller_object->$action($this->params);
                } else {
                    echo "Método '$action' não encontrado no controller '$controller'";
                }
            } else {
                echo "Controller '$controller' não encontrado.";
            }
        } else {
            echo "Página não encontrada (Erro 404) para a URL: " . htmlspecialchars($url) . " com o método " . $_SERVER['REQUEST_METHOD'];
        }
    }
}