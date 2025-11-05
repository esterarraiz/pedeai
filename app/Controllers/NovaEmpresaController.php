<?php
// Caminho: app/Controllers/NovaEmpresaController.php
// (Versão SIMPLIFICADA, apenas para carregar a view)

namespace App\Controllers;

class NovaEmpresaController
{
    public function __construct()
    {
        // Vazio, pois este controlador apenas exibe a View.
        // A lógica de POST está na API.
    }

    /**
     * Ação: [GET] /registrar
     * Apenas exibe a página de formulário de registo.
     */
    public function showRegistrationForm()
    {
        $pageTitle = 'Criar Nova Conta';
        $this->renderView('auth/registrar', ['pageTitle' => $pageTitle]);
    }

    /* --- Método de Ajuda (Helper) --- */
    protected function renderView(string $viewName, array $data = [])
    {
        extract($data);
        require_once dirname(__DIR__) . '/Views/' . $viewName . '.php';
    }
}