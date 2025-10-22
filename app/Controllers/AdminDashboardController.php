<?php

namespace App\Controllers;

use App\Core\Controller;
// (REMOVIDO) Não precisamos mais de 'CardapioModel' ou 'Database' neste arquivo
// use App\Models\CardapioModel;
// use Config\Database;

class AdminDashboardController extends Controller
{
    /**
     * (ALTERAÇÃO) O construtor agora aceita $route_params.
     * Isto é necessário por causa da nossa correção no Router.php.
     */
    public function __construct($route_params = [])
    {
        // Passa os parâmetros para o Controller pai (essencial para session_start())
        parent::__construct($route_params); 
        
        $this->requireLogin();
        
        if ($_SESSION['user_cargo'] !== 'administrador') {
            header('Location: /acesso-negado');
            exit;
        }
    }

    public function index()
    {
        $this->loadView('dashboard/admin', ['pageTitle' => 'Dashboard Administrador']);
    }

    /**
     * (ALTERAÇÃO) Este método agora SÓ carrega a view "casca".
     * A lógica de busca de dados foi movida para Api\AdminCardapioController@listar
     */
    public function gerenciarCardapio()
    {
        // (REMOVIDO) Toda a lógica de busca de dados foi removida daqui
        // $pdo = Database::getConnection();
        // $cardapioModel = new CardapioModel($pdo);
        // ...
        // $itensCardapio = $cardapioModel->buscarItensAgrupados($empresa_id);
        // $categorias = $cardapioModel->buscarTodasCategorias($empresa_id);

        $this->loadView('admin/cardapio', [
            'pageTitle'  => 'Gerenciar Cardápio',
            // (REMOVIDO) Não passamos mais 'cardapio' ou 'categorias'
            'activePage' => 'cardapio'
        ]);
    }

    /**
     * (REMOVIDO) Método movido para Api\AdminCardapioController@criar
     * A rota POST que apontava para cá foi desativada no Router.php
     */
    // public function adicionarItem() { ... }
    
    /**
     * (REMOVIDO) Método movido para Api\AdminCardapioController@atualizar
     * A rota POST que apontava para cá foi desativada no Router.php
     */
    // public function editarItem() { ... }

    /**
     * (REMOVIDO) Método movido para Api\AdminCardapioController@remover
     * A rota POST que apontava para cá foi desativada no Router.php
     */
    // public function removerItem() { ... }
}