<?php
// Ficheiro: app/Core/Router.php (Versão atualizada e corrigida)

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = [];

    public function __construct()
    {
        // === ROTAS DE AUTENTICAÇÃO (Acesso Público) ===
        $this->add('GET', 'login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('POST', 'login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('GET', 'logout', ['controller' => 'AuthController', 'action' => 'logout']);

        // === ROTAS DE ADMINISTRADOR ===
        $this->add('GET', 'dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index'], ['administrador']);
        $this->add('GET', 'funcionarios', ['controller' => 'FuncionarioController', 'action' => 'index'], ['administrador']);
        $this->add('GET', 'funcionarios/novo', ['controller' => 'FuncionarioController', 'action' => 'showCreateForm'], ['administrador']);
        $this->add('POST', 'funcionarios/criar', ['controller' => 'FuncionarioController', 'action' => 'create'], ['administrador']);
        $this->add('GET', 'funcionarios/editar/{id:\d+}', ['controller' => 'FuncionarioController', 'action' => 'showEditForm'], ['administrador']);
        $this->add('POST', 'funcionarios/atualizar', ['controller' => 'FuncionarioController', 'action' => 'update'], ['administrador']);
        $this->add('POST', 'funcionarios/status', ['controller' => 'FuncionarioController', 'action' => 'toggleStatus'], ['administrador']);
        $this->add('POST', 'funcionarios/redefinir-senha', ['controller' => 'FuncionarioController', 'action' => 'redefinirSenha'], ['administrador']);
        // Cardápio
        $this->add('GET', 'dashboard/admin/cardapio', ['controller' => 'AdminDashboardController', 'action' => 'gerenciarCardapio'], ['administrador']);
        $this->add('POST', 'dashboard/admin/cardapio/adicionar', ['controller' => 'AdminDashboardController', 'action' => 'adicionarItem'], ['administrador']);
        $this->add('POST', 'dashboard/admin/cardapio/editar', ['controller' => 'AdminDashboardController', 'action' => 'editarItem'], ['administrador']);
        $this->add('POST', 'dashboard/admin/cardapio/remover', ['controller' => 'AdminDashboardController', 'action' => 'removerItem'], ['administrador']);

        // === ROTAS DE CAIXA ===
        $this->add('GET', 'dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index'], ['caixa']);
        $this->add('GET', 'caixa/conta/{id:\d+}', ['controller' => 'Caixacontroller', 'action' => 'verConta'], ['caixa']);
        $this->add('POST', 'caixa/pagamento/processar', ['controller' => 'Caixacontroller', 'action' => 'processarPagamento'], ['caixa']);

        // === ROTAS DE GARÇOM (PÁGINAS ANTIGAS - REMOVIDAS PARA EVITAR CONFLITO) ===
        // A única rota de página para o garçom agora é o novo dashboard.
        $this->add('GET', 'dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index'], ['garçom']);
        
        // === ROTAS DE COZINHEIRO ===
        $this->add('GET', 'dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index'], ['cozinheiro']);
        $this->add('POST', 'cozinha/pedido/pronto', ['controller' => 'CozinheiroDashboardController', 'action' => 'marcarPronto'], ['cozinheiro']);

        // === API DO GARÇOM ===
        $this->add('GET', 'api/garcom/mesas', ['controller' => 'Api\\GarcomApiController', 'action' => 'listarMesas'], ['garçom']);
        $this->add('GET', 'api/garcom/mesas/{id:\\d+}', ['controller' => 'Api\\GarcomApiController', 'action' => 'detalhesMesa'], ['garçom']);
        $this->add('GET', 'api/garcom/cardapio', ['controller' => 'Api\\GarcomApiController', 'action' => 'getCardapio'], ['garçom']);
        $this->add('POST', 'api/garcom/pedidos', ['controller' => 'Api\\GarcomApiController', 'action' => 'lancarPedido'], ['garçom']);
        $this->add('GET', 'api/garcom/pedidos/prontos', ['controller' => 'Api\\GarcomApiController', 'action' => 'buscarPedidosProntos'], ['garçom']);
        $this->add('POST', 'api/garcom/pedidos/marcar-entregue', ['controller' => 'Api\\GarcomApiController', 'action' => 'marcarComoEntregue'], ['garçom']);
    }

    public function add($method, $route, $params = [], $roles = [])
    {
        $route = preg_replace('/\{([a-z]+):([^\}]+)\}/', '(?P<\1>\2)', $route);
        $route = '/^' . str_replace('/', '\/', $route) . '$/i';
        
        $this->routes[] = [
            'method' => strtoupper($method),
            'route'  => $route,
            'params' => $params,
            'roles'  => array_map('strtolower', $roles)
        ];
    }

    public function match($url)
    {
        $current_method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $routeInfo) {
            if ($routeInfo['method'] === $current_method && preg_match($routeInfo['route'], $url, $matches)) {
                $this->params = $routeInfo['params'];
                $this->params['roles'] = $routeInfo['roles'];
                
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $this->params[$key] = $value;
                    }
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
            $requiredRoles = $this->params['roles'] ?? [];

            if (empty($requiredRoles)) {
                $this->executeAction();
                return;
            }

            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
                exit;
            }

            $userRole = strtolower($_SESSION['user_cargo'] ?? '');

            if ($userRole === 'administrador' || in_array($userRole, $requiredRoles)) {
                $this->executeAction();
            } else {
                $this->showErrorPage('error/403', 403); // Acesso negado
            }
        } else {
            $this->showErrorPage('error/404', 404); // Rota não encontrada
        }
    }

    protected function executeAction()
    {
        $controller = "App\\Controllers\\" . $this->params['controller'];

        if (class_exists($controller)) {
            $controller_object = new $controller(); 
            $action = $this->params['action'];

            if (method_exists($controller_object, $action)) {
                $controller_object->$action($this->params);
            } else {
                // Em vez de 'echo', usamos a página de erro para consistência
                $this->showErrorPage('error/500', 500);
                error_log("Método '$action' não encontrado no controller '$controller'");
            }
        } else {
            $this->showErrorPage('error/500', 500);
            error_log("Controller '$controller' não encontrado.");
        }
    }

    protected function showErrorPage($viewName, $statusCode)
    {
        http_response_code($statusCode);
        $viewPath = dirname(__DIR__) . '/Views/' . $viewName . '.php'; // Corrigido 'views' para 'Views'
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>Erro {$statusCode}</h1><p>Página de erro não encontrada.</p>";
        }
        exit;
    }
}