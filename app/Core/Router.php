<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = []; // Parâmetros da rota (controller, action)
    protected $url_params = []; // Parâmetros da URL (ex: o ID)

    public function __construct()
    {
        // === ROTAS DE AUTENTICAÇÃO ===
        $this->add('login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('logout', ['controller' => 'AuthController', 'action' => 'logout']);
        
        // === ROTAS DE DASHBOARDS ===
        $this->add('dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index']);
        $this->add('dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index']);
        $this->add('dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index']);
        $this->add('dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index']);
        $this->add('dashboard/generico', ['controller' => 'GenericDashboardController', 'action' => 'index']);

        // === ROTAS DE PEDIDOS ===
        // Rota para MOSTRAR o formulário de novo pedido (ex: /pedidos/novo/5)
        $this->add('pedidos/novo/{id}', ['controller' => 'PedidoController', 'action' => 'showFormNovoPedido']);
        // Rota para PROCESSAR o formulário de novo pedido
        $this->add('pedidos/criar', ['controller' => 'PedidoController', 'action' => 'criarPedido']);

        // === ROTAS DE MESAS ===
        $this->add('mesas', ['controller' => 'MesaController', 'action' => 'index']);
        // Rota para PROCESSAR a liberação da mesa (requisição AJAX)
        $this->add('mesas/liberar', ['controller' => 'MesaController', 'action' => 'liberarMesa']);
    }

    public function add($route, $params = [])
    {
        // Converte a rota em uma expressão regular, tratando parâmetros como {id}
        $route = str_replace('{id}', '(\d+)', $route);
        $route = preg_replace('/\//', '\\/', $route);
        $route = '/^' . $route . '$/i';
        $this->routes[$route] = $params;
    }

    public function match($url)
    {
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                // Remove o primeiro elemento ($matches[0]), que é a URL completa
                array_shift($matches);
                
                // Salva os parâmetros capturados da URL (ex: o ID)
                $this->url_params = $matches;
                
                // Salva os parâmetros da rota (controller e action)
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    public function dispatch()
    {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $url = trim($url, '/');
        
        if ($this->match($url)) {
            $controller = "App\\Controllers\\" . $this->params['controller'];

            if (class_exists($controller)) {
                $controller_object = new $controller(); 
                $action = $this->params['action'];

                if (method_exists($controller_object, $action)) {
                    // Chama a ação, passando os parâmetros da URL (como o ID)
                    $controller_object->$action($this->url_params);
                } else {
                    http_response_code(500);
                    echo "Método '$action' não encontrado no controller '$controller'";
                }
            } else {
                http_response_code(500);
                echo "Controller '$controller' não encontrado.";
            }
        } else {
            http_response_code(404);
            echo "Página não encontrada (Erro 404) para a URL: " . htmlspecialchars($url);
        }
    }
}