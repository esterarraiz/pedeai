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


    protected function before()
    {

    }


    protected function after()
    {

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
            header('Location: /dashboard');
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
        // Constrói o caminho para o arquivo da view
        // Ex: ../app/Views/pedidos/novo.php
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';

        // Verifica se o arquivo da view realmente existe
        if (file_exists($viewPath)) {
            // A função extract() é muito útil aqui.
            // Ela transforma as chaves de um array em variáveis.
            // Ex: $data['mesa_id'] se torna a variável $mesa_id dentro da view.
            extract($data);

            // Inclui o arquivo da view, que agora tem acesso às variáveis extraídas
            require_once $viewPath;
        } else {
            // Se o arquivo da view não for encontrado, exibe um erro claro.
            http_response_code(500);
            echo "Erro: Arquivo de View não encontrado em: " . htmlspecialchars($viewPath);
            exit;
        }
    }

}