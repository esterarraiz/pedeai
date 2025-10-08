<?php

namespace App\Core;

abstract class Controller
{
    protected $route_params = [];

    public function __construct($route_params = [])
    {
        $this->route_params = $route_params;
        
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function __call($name, $args)
    {
        if (method_exists($this, $name)) {
            if ($this->before() !== false) {
                call_user_func_array([$this, $name], $args);
                $this->after();
            }
        } else {
            echo "Método $name não encontrado no controller " . get_class($this);
        }
    }

    protected function before() {}

    protected function after() {}

    protected function requireLogin()
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * FUNÇÃO CORRIGIDA PARA QUEBRAR O LOOP
     */
    protected function requireLogout()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            // Pega o ID do cargo do usuário da sessão
            $cargo_id = $_SESSION['user_cargo_id'] ?? null;
            
            // Redireciona para o dashboard específico do cargo
            switch ($cargo_id) {
                case 1: header('Location: /dashboard/admin'); break;
                case 2: header('Location: /dashboard/garcom'); break;
                case 3: header('Location: /dashboard/caixa'); break;
                case 4: header('Location: /dashboard/cozinheiro'); break;
                default: header('Location: /dashboard/generico'); break;
            }
            exit;
        }
    }

    protected function loadView($viewName, $data = [])
    {
        extract($data);
        
        $file = dirname(__DIR__) . "/Views/$viewName.php";
        if (is_readable($file)) {
            require $file;
        } else {
            throw new \Exception("View '$file' não encontrada.");
        }
    }

    public function renderView(string $view, array $data = [])
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        if (file_exists($viewPath)) {
            extract($data);
            require_once $viewPath;
        } else {
            http_response_code(500);
            echo "Erro: Arquivo de View não encontrado em: " . htmlspecialchars($viewPath);
            exit;
        }
    }
}