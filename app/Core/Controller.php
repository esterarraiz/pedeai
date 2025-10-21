<?php
// Ficheiro: app/Core/Controller.php (Versão Simplificada)

namespace App\Core;

abstract class Controller
{
    protected $route_params = [];

    public function __construct($route_params = [])
    {
        // A lógica de session_start() foi movida para public/index.php
        // para garantir que é executada uma única vez no início de tudo.
        $this->route_params = $route_params;
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
    
    protected function requireLogout()
    {
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            $cargo = $_SESSION['user_cargo'] ?? null;
            switch ($cargo) {
                case 'administrador': header('Location: /dashboard/admin'); break;
                case 'garçom': header('Location: /dashboard/garcom'); break;
                case 'caixa': header('Location: /dashboard/caixa'); break;
                case 'cozinheiro': header('Location: /dashboard/cozinheiro'); break;
                default: header('Location: /'); break;
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
}