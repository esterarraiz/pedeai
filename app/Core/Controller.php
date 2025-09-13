<?php

namespace App\Core;

/**
 * Controller Base
 * Todos os outros controllers devem herdar desta classe.
 */
abstract class Controller
{
    /**
     * Parâmetros da rota (como 'id')
     */
    protected $route_params = [];

    public function __construct($route_params = [])
    {
        $this->route_params = $route_params;
        
        // Inicia a sessão em TODAS as requisições que passam por um controller
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Método mágico para lidar com ações de controller que não existem.
     */
    public function __call($name, $args)
    {
        if (method_exists($this, $name)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $name], $args);
                $this->after();
            }
        } else {
            echo "Método $name não encontrado no controller " . get_class($this);
            // Idealmente, jogue uma Exceção aqui ou mostre uma página 404
        }
    }

    /**
     * Filtro 'Before' - executado antes de qualquer ação do controller.
     * Útil para, por exemplo, verificar autenticação.
     */
    protected function before()
    {
        // Pode ser sobreescrito por controllers filhos
    }

    /**
     * Filtro 'After' - executado depois da ação do controller.
     */
    protected function after()
    {
        // Pode ser sobreescrito por controllers filhos
    }

    /**
     * Exige que o usuário esteja logado para acessar a página.
     * Se não estiver, redireciona para /login.
     */
    protected function requireLogin()
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Exige que o usuário esteja DESLOGADO (ex: para acessar a pág de login).
     * Se estiver logado, redireciona para o /dashboard.
     */
    protected function requireLogout()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: /dashboard');
            exit;
        }
    }

    /**
     * Função helper para carregar uma View
     * (Você vai precisar disso eventualmente)
     */
    protected function loadView($viewName, $data = [])
    {
        // Extrai os dados para variáveis locais (ex: $data['titulo'] vira $titulo)
        extract($data);
        
        $file = dirname(__DIR__) . "/Views/$viewName.php";
        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("View '$file' não encontrada.");
        }
    }
}