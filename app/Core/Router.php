<?php

namespace App\Core;

class Router
{
    protected $routes = [];
    protected $params = []; // Armazenará TODOS os parâmetros (rota + URL)

    public function __construct()
    {
        // === ROTA DA LANDING PAGE ===
        $this->add('GET', '', ['controller' => 'HomeController', 'action' => 'index']);
        // === ROTAS DE AUTENTICAÇÃO (Acesso Público) ===
        $this->add('GET', 'login', ['controller' => 'AuthController', 'action' => 'showLogin']);
        $this->add('POST', 'login/process', ['controller' => 'AuthController', 'action' => 'processLogin']);
        $this->add('GET', 'logout', ['controller' => 'AuthController', 'action' => 'logout']);
        // === ROTAS DA API DE AUTENTICAÇÃO (PÚBLICAS) ===
        $this->add('POST', 'api/login', ['controller' => 'Api\AuthController', 'action' => 'login']);
        // $this->add('POST', 'api/register', ['controller' => 'Api\AuthController', 'action' => 'register']); // Exemplo

        // === ROTAS PROTEGIDAS POR SESSÃO ===

        // --- ADMIN ---
        $this->add('GET', 'dashboard/admin', ['controller' => 'AdminDashboardController', 'action' => 'index'], ['administrador']);
        // Admin: Gerenciamento de Funcionários (Views)
        $this->add('GET', 'funcionarios', ['controller' => 'FuncionarioController', 'action' => 'index'], ['administrador']);
        $this->add('GET', 'funcionarios/novo', ['controller' => 'FuncionarioController', 'action' => 'showCreateForm'], ['administrador']);
        $this->add('GET', 'funcionarios/editar/{id:\d+}', ['controller' => 'FuncionarioController', 'action' => 'showEditForm'], ['administrador']);
        // Admin: Gerenciamento de Funcionários (Form Handlers - podem ser migrados para API)
        $this->add('POST', 'funcionarios/criar', ['controller' => 'FuncionarioController', 'action' => 'create'], ['administrador']);
        $this->add('POST', 'funcionarios/atualizar', ['controller' => 'FuncionarioController', 'action' => 'update'], ['administrador']);
        // Admin: Gerenciamento de Funcionários (API Endpoints)
        $this->add('POST', 'api/funcionarios/status', ['controller' => 'Api\FuncionarioController', 'action' => 'toggleStatus'], ['administrador']);
        $this->add('POST', 'api/funcionarios/redefinir-senha', ['controller' => 'Api\FuncionarioController', 'action' => 'redefinirSenha'], ['administrador']);
        
        // Admin: Gerenciamento de Cardápio (Views)
        $this->add('GET', 'dashboard/admin/cardapio', ['controller' => 'AdminDashboardController', 'action' => 'gerenciarCardapio'], ['administrador']);
        // Admin: Gerenciamento de Cardápio (API Endpoints - refatorado de POST para API)
        $this->add('POST', 'api/cardapio/adicionar', ['controller' => 'Api\CardapioController', 'action' => 'adicionarItem'], ['administrador']);
        $this->add('POST', 'api/cardapio/editar', ['controller' => 'Api\CardapioController', 'action' => 'editarItem'], ['administrador']);
        $this->add('POST', 'api/cardapio/remover', ['controller' => 'Api\CardapioController', 'action' => 'removerItem'], ['administrador']);

        // === ROTAS DE GARÇOM (VIEWS) ===
        $this->add('GET', 'dashboard/garcom', ['controller' => 'GarcomDashboardController', 'action' => 'index'], ['garçom']);
        $this->add('GET', 'mesas/detalhes/{id:\d+}', ['controller' => 'MesaController', 'action' => 'showDetalhesMesa'], ['garçom']); // View Controller Detalhes (pode ser refatorado para API)
        $this->add('GET', 'pedidos/novo/{id:\d+}', ['controller' => 'PedidoController', 'action' => 'showFormNovoPedido'], ['garçom']); // View Lançar Pedido

        // === ROTAS DA API DO GARÇOM ===
        $this->add('GET', 'api/garcom/mesas', ['controller' => 'Api\GarcomApiController', 'action' => 'listarMesas'], ['garçom']);
        $this->add('GET', 'api/garcom/mesas/{id:\d+}', ['controller' => 'Api\GarcomApiController', 'action' => 'detalhesMesa'], ['garçom']); // Endpoint para detalhes via API
        $this->add('GET', 'api/garcom/cardapio', ['controller' => 'Api\GarcomApiController', 'action' => 'getCardapio'], ['garçom']); // Endpoint para buscar cardápio
        $this->add('POST', 'api/pedidos', ['controller' => 'Api\PedidoController','action' => 'criarPedido'], ['garçom']); // Endpoint CORRETO para criar pedido
        $this->add('GET', 'api/garcom/pedidos/prontos', ['controller' => 'Api\GarcomApiController', 'action' => 'buscarPedidosProntos'], ['garçom']);
        $this->add('POST', 'api/garcom/pedidos/marcar-entregue', ['controller' => 'Api\GarcomApiController', 'action' => 'marcarComoEntregue'], ['garçom']);

        // --- CAIXA (VIEWS) ---
        $this->add('GET', 'dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index'], ['caixa']);
        $this->add('GET', 'caixa/conta/{id:\d+}', ['controller' => 'CaixaController', 'action' => 'verConta'], ['caixa']); // View
        
        $this->add('GET', 'dashboard/caixa', ['controller' => 'CaixaDashboardController', 'action' => 'index'], ['caixa']);
        // Esta rota carrega a "casca" da página de detalhes da conta
        $this->add('GET', 'caixa/conta/{id:\d+}', ['controller' => 'CaixaController', 'action' => 'verConta'], ['caixa']); 
        
        // === API DO CAIXA (NOVAS) ===
        $this->add('GET', 'api/caixa/mesas-abertas', ['controller' => 'Api\CaixaApiController', 'action' => 'getMesasAbertas'], ['caixa']);
        $this->add('GET', 'api/caixa/conta/{id:\d+}', ['controller' => 'Api\CaixaApiController', 'action' => 'getDetalhesConta'], ['caixa']);
        $this->add('POST', 'api/caixa/pagamento', ['controller' => 'Api\CaixaApiController', 'action' => 'processarPagamento'], ['caixa']);

        $this->add('GET', 'api/garcom/mesas', ['controller' => 'Api\GarcomApiController', 'action' => 'listarMesas'], ['garçom']);
        $this->add('GET', 'api/garcom/mesas/{id:\d+}', ['controller' => 'Api\GarcomApiController', 'action' => 'detalhesMesa'], ['garçom']);
        $this->add('GET', 'api/garcom/cardapio', ['controller' => 'Api\GarcomApiController', 'action' => 'getCardapio'], ['garçom']);
        $this->add('POST', 'api/pedidos', ['controller' => 'Api\PedidoController','action' => 'criarPedido'], ['garçom']);
        $this->add('GET', 'api/pedidos/prontos', ['controller' => 'Api\PedidoController', 'action' => 'getPedidosProntos'], ['garçom']);
        $this->add('POST', 'api/pedidos/marcar-entregue', ['controller' => 'Api\PedidoController', 'action' => 'marcarPedidoEntregue'], ['garçom']);

        // --- COZINHEIRO ---
        $this->add('GET', 'dashboard/cozinheiro', ['controller' => 'CozinheiroDashboardController', 'action' => 'index'], ['cozinheiro']); // View (SPA)
        // API Cozinheiro
        $this->add('GET', 'api/pedidos/cozinha', ['controller' => 'Api\PedidoController', 'action' => 'getPedidosParaCozinha'], ['cozinheiro']);
        $this->add('POST', 'api/pedidos/marcar-pronto', ['controller' => 'Api\PedidoController', 'action' => 'marcarPedidoPronto'], ['cozinheiro']);

        // --- ROTA GENÉRICA (Fallback) ---
        $this->add('GET', 'dashboard/generico', ['controller' => 'GenericDashboardController', 'action' => 'index'], ['administrador', 'garçom', 'caixa', 'cozinheiro']);

         // === (NOVO) API DO ADMIN (Cardápio) ===
        // Adicionadas as rotas RESTful para o cardápio
        $this->add('GET', 'api/admin/cardapio', 
            ['controller' => 'Api\\AdminCardapioController', 'action' => 'listar'], ['administrador']);

        $this->add('POST', 'api/admin/cardapio', 
            ['controller' => 'Api\\AdminCardapioController', 'action' => 'criar'], ['administrador']);

        $this->add('PUT', 'api/admin/cardapio/{id:\d+}', 
            ['controller' => 'Api\\AdminCardapioController', 'action' => 'atualizar'], ['administrador']);

        $this->add('DELETE', 'api/admin/cardapio/{id:\d+}', 
            ['controller' => 'Api\\AdminCardapioController', 'action' => 'remover'], ['administrador']);
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
                    if (is_string($key)) $this->params[$key] = $value;
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
            $userRole = strtolower($_SESSION['user_cargo'] ?? '');

            // 1. Se a rota não exige cargo (é pública como /login), executa imediatamente.
            if (empty($requiredRoles)) {
                $this->executeAction();
                return;
            }

            // 2. A partir daqui, todas as rotas são protegidas. Verifica se o utilizador está logado.
            if (!isset($_SESSION['user_id'])) {
                // Se for uma chamada de API, retorna JSON. Se for uma página, redireciona.
                if (str_starts_with($url, 'api/')) {
                    $this->showErrorPage(null, 401, 'Utilizador não autenticado.'); // 401 Unauthorized
                } else {
                    header('Location: /login');
                    exit;
                }
                return;
            }

            // 3. Se está logado, verifica se tem o cargo correto (ou se é admin).
            if ($userRole === 'administrador' || in_array($userRole, $requiredRoles)) {
                $this->executeAction();
            } else {
                // O utilizador está logado, mas não tem permissão.
                if (str_starts_with($url, 'api/')) {
                    $this->showErrorPage(null, 403, 'Acesso negado.'); // 403 Forbidden
                } else {
                    $this->showErrorPage('error/403', 403);
                }
            }
        } else {
            // Nenhuma rota foi encontrada.
            if (str_starts_with($url, 'api/')) {
                $this->showErrorPage(null, 404, 'Endpoint não encontrado.'); // 404 Not Found
            } else {
                $this->showErrorPage('error/404', 404);
            }
        }
    }

    protected function executeAction()
    {
        // Ajusta o namespace se o controller estiver na pasta Api
        $controllerNamespace = "App\\Controllers\\";
        if (str_contains($this->params['controller'], 'Api\\')) {
            $controllerNamespace = "App\\Controllers\\"; // O namespace já está completo no 'add'
        }
        $controller = $controllerNamespace . $this->params['controller'];

        if (class_exists($controller)) {
            $controller_object = new $controller();
            $action = $this->params['action'];
            if (method_exists($controller_object, $action)) {
                $controller_object->$action($this->params);
            } else {
                $this->showErrorPage('error/500', 500);
                error_log("Método '$action' não encontrado no controller '$controller'");
            }
        } else {
            $this->showErrorPage('error/500', 500);
            error_log("Controller '$controller' não encontrado.");
        }
    }

    protected function showErrorPage($viewName, $statusCode, $apiMessage = null)
    {
        http_response_code($statusCode);
        
        // Se for um erro de API, retorna JSON
        if ($apiMessage !== null) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $apiMessage]);
            exit;
        }

        // Se for um erro de View, carrega a página HTML
        $viewPath = dirname(__DIR__) . '/Views/' . $viewName . '.php'; // Assumindo pasta 'Views' com 'V' maiúsculo
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            echo "<h1>Erro {$statusCode}</h1><p>Página de erro não encontrada.</p>";
        }
        exit;
    }
}

