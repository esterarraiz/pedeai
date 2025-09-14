<?php

namespace App\Controllers;

use App\Core\Controller;

class GarcomDashboardController extends Controller
{
    /**
     * Exibe a tela principal do garçom.
     */
    public function index()
    {
        // 1. Protege a rota: exige que o usuário esteja logado.
        $this->requireLogin();

        // 2. (Opcional, mas recomendado) Garante que apenas um Garçom acesse esta página.
        if ($_SESSION['user_cargo_id'] != 2) {
            // Se não for garçom, redireciona para o logout ou uma página de "acesso negado".
            header('Location: /logout');
            exit;
        }
        
        // 3. Carrega a view ou exibe o conteúdo do dashboard do garçom
        // Por enquanto, vamos apenas exibir uma mensagem de boas-vindas.
        echo "<h1>Bem-vindo ao Dashboard do Garçom, " . htmlspecialchars($_SESSION['user_nome']) . "!</h1>";
        echo "<p>Aqui será implementado o painel principal do Garçom (Dashboard) e navegação.</p>";
        echo '<a href="/logout">Sair</a>';

    }
}
