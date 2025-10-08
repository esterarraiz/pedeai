<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = []; // Armazenará TODOS os parâmetros (rota + URL)

    public function __construct()
    {
        // === ROTAS DE AUTENTICAÇÃO ===
        $this->add('GET', 'login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('POST', 'login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('GET', 'logout', ['controller' => 'AuthController', 'action' => 'logout']);
        
        // === ROTAS DE ADMIN (só o admin pode aceder) ===
        $this->add('GET', 'dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index'], ['administrador']);
        $this->add('GET', 'funcionarios', ['controller' => 'FuncionarioController', 'action' => 'index'], ['administrador']);
        $this->add('GET', 'funcionarios/novo', ['controller' => 'FuncionarioController', 'action' => 'showCreateForm'], ['administrador']);
        $this->add('POST', 'funcionarios/criar', ['controller' => 'FuncionarioController', 'action' => 'create'], ['administrador']);
        $this->add('GET', 'funcionarios/editar/{id:\d+}', ['controller' => 'FuncionarioController', 'action' => 'showEditForm'], ['administrador']);
        $this->add('POST', 'funcionarios/atualizar', ['controller' => 'FuncionarioController', 'action' => 'update'], ['administrador']);
        $this->add('POST', 'funcionarios/status', ['controller' => 'FuncionarioController', 'action' => 'toggleStatus'], ['administrador']);
        $this->add('POST', 'funcionarios/redefinir-senha', ['controller' => 'FuncionarioController', 'action' => 'redefinirSenha'], ['administrador']);
        
        // === ROTAS DE CAIXA (caixa e admin) ===
        $this->add('GET', 'dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index'], ['caixa']);
        $this->add('GET', 'caixa/conta/{id:\d+}', ['controller' => 'CaixaController', 'action' => 'verConta'], ['caixa']);
        $this->add('POST', 'caixa/pagamento/processar', ['controller' => 'CaixaController', 'action' => 'processarPagamento'], ['caixa']);

        // === ROTAS DE GARÇOM (garçom e admin) ===
        $this->add('GET', 'dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index'], ['garçom']);
        $this->add('GET', 'mesas', ['controller' => 'MesaController', 'action' => 'index'], ['garçom']);
        $this->add('GET', 'mesas/detalhes/{id:\d+}', ['controller' => 'MesaController', 'action' => 'showDetalhesMesa'], ['garçom']);
        $this->add('POST', 'mesas/liberar', ['controller' => 'MesaController', 'action' => 'liberarMesa'], ['garçom']);
        $this->add('GET', 'pedidos/novo/{id:\d+}', ['controller' => 'PedidoController', 'action' => 'showFormNovoPedido'], ['garçom']);
        $this->add('POST', 'pedidos/processar-ajax', ['controller' => 'PedidoController', 'action' => 'processarPedidoAjax'], ['garçom']);
      
        // === ROTAS cozinha ===
        $this->add('GET', 'dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index']);
        
        // === NOVA ROTA PARA A AÇÃO DA COZINHA ===
        $this->add('POST', 'cozinha/pedido/pronto', ['controller' => 'CozinheiroDashboardController', 'action' => 'marcarPronto']);
        
        $this->add('GET', 'dashboard/generico', ['controller' => 'GenericDashboardController', 'action' => 'index']);

        // === ROTAS DE PEDIDOS ===
        $this->add('GET', 'pedidos/novo/{id:\d+}', ['controller' => 'PedidoController', 'action' => 'showFormNovoPedido']);
        $this->add('POST', 'pedidos/criar', ['controller' => 'PedidoController', 'action' => 'criarPedido']);
        $this->add('POST', 'pedidos/processar-ajax', ['controller' => 'PedidoController', 'action' => 'processarPedidoAjax']);
        
        // === NOVA ROTA PARA BUSCAR PEDIDOS PRONTOS (PARA O GARÇOM) ===
        $this->add('GET', 'pedidos/prontos', ['controller' => 'PedidoController', 'action' => 'buscarPedidosProntos']);

        // === ROTAS DE MESAS ===
        $this->add('GET', 'mesas', ['controller' => 'MesaController', 'action' => 'index']);
        $this->add('POST', 'mesas/liberar', ['controller' => 'MesaController', 'action' => 'liberarMesa']);
        $this->add('GET', 'mesas/detalhes/{id:\d+}', ['controller' => 'MesaController', 'action' => 'showDetalhesMesa']);
    }
    
    /**
     * AQUI ESTÁ A CORREÇÃO!
     * Adicionamos $roles = [] como um parâmetro opcional.
     */
    public function add($method, $route, $params = [], $roles = [])
    {
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . str_replace('/', '\/', $route) . '$/i';
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'route'  => $route,
            'params' => $params,
            'roles'  => array_map('strtolower', $roles) // Agora $roles sempre será um array
        ];
    }

    public function match($url)
    {
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
    
    public function dispatch()
    {
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