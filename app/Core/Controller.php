<?php
// Ficheiro: app/Core/Controller.php (Versão Definitiva e Corrigida)

namespace App\Core;

abstract class Controller
{
    public function __construct()
    {
        // A lógica de session_start() está em public/index.php.
    }

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
            $location = '/'; 
            switch ($cargo) {
                case 'administrador': $location = '/dashboard/admin'; break;
                case 'garçom': $location = '/dashboard/garcom'; break;
                case 'caixa': $location = '/dashboard/caixa'; break;
                case 'cozinheiro': $location = '/dashboard/cozinheiro'; break;
            }
            header('Location: ' . $location);
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