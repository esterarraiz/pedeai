<?php

namespace App\Controllers;

/**
 * HomeController
 * * Este controller é responsável por carregar a página inicial (landing page) do sistema.
 */
class HomeController
{
    /**
     * Renderiza a view da landing page.
     * * @return void
     */
    public function index()
    {
        // Define o caminho para o arquivo da view.
        // É uma boa prática mover o 'index.html' para dentro da pasta de views e renomeá-lo para '.php'.
        $viewPath = dirname(__DIR__) . '/Views/home/index.php';

        if (file_exists($viewPath)) {
            // Carrega o arquivo da view, que contém todo o HTML da landing page.
            require $viewPath;
        } else {
            // Exibe uma mensagem de erro caso o arquivo da view não seja encontrado.
            http_response_code(500);
            echo "<h1>Erro 500</h1><p>O arquivo da página inicial não foi encontrado.</p>";
        }
    }
}
